<?php
/**
 * Behavior for make ActiveRecord model taggable
 *
 * @link https://github.com/inblank/yii2-taggable
 * @copyright Copyright (c) 2016 Pavel Aleksandrov <inblank@yandex.ru>
 * @license http://opensource.org/licenses/MIT
 */

namespace inblank\taggable;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * Behavior for make ActiveRecord model taggable
 *
 * @property ActiveRecord $owner
 *
 * @author Pavel Aleksandrov <inblank@yandex.ru>
 */
class TaggableBehavior extends Behavior
{
    /**
     * @var string[] list of tags
     */
    protected $_tagsList;

    /**
     * @var bool sign that owner has attribute `tags`
     */
    protected $_hasTagAttribute;

    /**
     * @var int[] store tag ids for delete when owner deleting
     */
    private $_tagsForDelete;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * Add tags
     * @param string|string[] $tags list of tags added to the current. If specified as a string, the tags must be separated by commas
     */
    public function addTags($tags)
    {
        $this->_tagsList = array_unique(array_merge($this->getTagValues(), $this->parseTags($tags)));
        $this->updateOwnerTags();
    }

    /**
     * Get tags list
     * @param bool $asString sign get a list of tags as string
     * @return string|string[]
     */
    public function getTagValues($asString = false)
    {
        if ($this->_tagsList === null && !$this->owner->getIsNewRecord()) {
            // the list of tags is not initialized
            $this->_tagsList = [];
            // trying to obtain related models
            $relation = $this->owner->getRelation('tagsList', false);
            if ($relation) {
                /** @var ActiveRecord $tag */
                foreach ($relation->all() as $tag) {
                    $this->_tagsList[] = $tag->getAttribute('text');
                }
                $this->_tagsList = array_unique($this->_tagsList);
            }
        }
        return $asString === true ? implode(',', $this->_tagsList) : $this->_tagsList;
    }

    /**
     * check that the owner has the attribute `tags`
     * @return bool
     */
    protected function ownerHasTagAttribute()
    {
        if ($this->_hasTagAttribute === null) {
            $this->_hasTagAttribute = $this->owner->hasAttribute('tags');
        }
        return $this->_hasTagAttribute;
    }

    /**
     * Parse tags list into array
     * @param string|string[] $tags list of tags. If specified as a string, the tags must be separated by commas
     * @return string[]
     */
    protected function parseTags($tags)
    {
        return array_unique(is_array($tags) ? array_filter($tags) : preg_split('/\s*,\s*/', $tags, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Update the attribute `tags` of the owner
     */
    public function updateOwnerTags()
    {
        if ($this->ownerHasTagAttribute()) {
            $this->owner->setAttribute('tags', $this->getTagValues(true));
        }
    }

    /**
     * Set tags. All old tags will be replaced with new
     * @param string|string[] $tags a new list of tags. If specified as a string, the tags must be separated by commas
     */
    public function setTagValues($tags)
    {
        $this->_tagsList = $this->parseTags($tags);
        $this->updateOwnerTags();
    }

    /**
     * Remove all values from tags list
     */
    public function removeAllTagValues()
    {
        $this->_tagsList = [];
        $this->updateOwnerTags();
    }

    /**
     * Returns a value indicating whether tags exists
     * @param string|string[] $tags
     * @return boolean
     */
    public function hasTags($tags)
    {
        return empty(array_diff($this->parseTags($tags), $this->getTagValues()));
    }

    /**
     * Removes the specified tags from the list
     * @param string|string[] $tags the list of removed tags. If specified as a string, the tags must be separated by commas
     */
    public function removeTagValues($tags)
    {
        $this->_tagsList = array_diff($this->getTagValues(), $this->parseTags($tags));
        $this->updateOwnerTags();
    }

    /**
     * After owner save action
     */
    public function afterSave()
    {
        if ($this->_tagsList !== null) {
            if (!$this->owner->getIsNewRecord()) {
                // clear old tags
                $this->beforeDelete();
                $this->afterDelete();
            }
            $relation = $this->owner->getRelation('tagsList', false);
            if ($relation) {
                /** @var ActiveRecord $relationClass */
                $relationClass = $relation->modelClass;
                $ownerTagsList = [];
                foreach ($this->_tagsList as $tagText) {
                    /* @var ActiveRecord $tag */
                    $tag = $relationClass::findOne(['text' => $tagText]);
                    if ($tag === null) {
                        $tag = new $relationClass();
                        $tag->setAttribute('text', $tagText);
                    }
                    $tag->setAttribute('count', $tag->getAttribute('count') + 1);
                    if ($tag->save()) {
                        $ownerTagsList[] = [$this->owner->getPrimaryKey(), $tag->getPrimaryKey()];
                    }
                }
                if (!empty($ownerTagsList)) {
                    $this->owner->getDb()
                        ->createCommand()
                        ->batchInsert($relation->via->from[0], [key($relation->via->link), current($relation->link)], $ownerTagsList)
                        ->execute();
                }
            }
        }
    }

    /**
     * Before delete event
     */
    public function beforeDelete()
    {
        // store tag ids list
        $this->_tagsForDelete = [];
        $relation = $this->owner->getRelation('tagsList', false);
        if ($relation) {
            $this->_tagsForDelete = (new Query())
                ->select(current($relation->link))
                ->from($relation->via->from[0])
                ->where([key($relation->via->link) => $this->owner->getPrimaryKey()])
                ->column($this->owner->getDb());
        }
    }

    /**
     * After delete event
     */
    public function afterDelete()
    {
        // after complete owner delete delete tags links
        if (!empty($this->_tagsForDelete)) {
            $relation = $this->owner->getRelation('tagsList', false);
            if (!empty($relation)) {
                /** @var ActiveRecord $class */
                $class = $relation->modelClass;
                // decrease counters
                $class::updateAllCounters(['count' => -1], ['in', $class::primaryKey(), $this->_tagsForDelete]);
                // delete links
                $this->owner->getDb()
                    ->createCommand()
                    ->delete($relation->via->from[0], [key($relation->via->link) => $this->owner->getPrimaryKey()])
                    ->execute();
            }
            $this->_tagsForDelete = [];
        }
    }
}
