<?php

namespace app\models;

use inblank\taggable\TaggableBehavior;
use yii\db\ActiveRecord;

/**
 * Test model class
 *
 * @property int $id
 * @property string $title
 * @property string $tags
 * @property Tag[] $tagsList
 *
 * @method setTagValues($tags)
 * @method addTags($tags)
 * @method getTagValues()
 * @method removeAllTagValues()
 * @method removeTagValues($tags)
 * @method hasTags($tags)
 *
 * @package app\models
 */
class Model extends ActiveRecord
{

    public static function tableName()
    {
        return 'model';
    }

    function behaviors()
    {
        return [
            TaggableBehavior::className(),
        ];
    }

    public function rules()
    {
        return [
            ['title', 'required'],
            ['title', 'string', 'max' => 250],
            ['tags', 'default', 'value'=>''],
            ['tags', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTagsList()
    {
        return $this->hasMany(Tag::className(), ['id' => 'tag_id'])->viaTable('models_tags', ['model_id' => 'id']);
    }
}
