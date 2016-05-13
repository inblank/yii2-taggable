<?php

namespace inblank\taggable\tests;

use app\models\Model;
use app\models\Tag;
use Codeception\Specify;
use yii;
use yii\codeception\TestCase;

class TagsTest extends TestCase
{
    use Specify;

    /**
     * General test for Page model
     */
    public function testGeneral()
    {
        /** @var Model $model */
        $model = new Model();
        $model->setAttribute('title', 'Test model 1');
        $model->setTagValues('tag1, tag2');

        $this->specify("in new model we must view added tags", function () use ($model){
            expect("model attribute `tag` must content added tags", $model->tags)->equals('tag1,tag2');
            expect("behavior method must return tags list", $model->getTagValues())->equals(['tag1','tag2']);
        });

        expect("model with tags must be save", $model->save())->true();
        $this->specify("after save new model with tags we must view tags", function () use ($model){
            expect("model attribute `tag` must content added tags", $model->tags)->equals('tag1,tag2');
            $tagsList = $model->getTagsList()->orderBy('text')->all();
            expect("we must find link on tags", yii\helpers\ArrayHelper::getColumn($tagsList, 'text'))->equals(['tag1','tag2']);
            expect("tags count must be equal", yii\helpers\ArrayHelper::getColumn($tagsList, 'count'))->equals([1,1]);
            expect("we must get tags by behavior", $model->getTagValues())->equals(['tag1','tag2']);
        });

        $model = Model::findOne($model->id);
        expect("we must get saved model", $model)->notEmpty();
        $this->specify("after find model with tags we must view tags", function () use ($model){
            expect("model attribute `tag` must content added tags", $model->tags)->equals('tag1,tag2');
            expect("we must find link on tags", yii\helpers\ArrayHelper::getColumn($model->getTagsList()->orderBy('text')->all(), 'text'))->equals(['tag1','tag2']);
        });

        $this->specify("check has tag", function () use ($model){
            expect("model must has tags", $model->hasTags(['tag1','tag2']))->true();
            expect("model must has tags (as string)", $model->hasTags('tag1,tag2'))->true();
            expect("model must has tag", $model->hasTags('tag2'))->true();
            expect("model can't has tag", $model->hasTags('tag3'))->false();
        });

        $model->removeTagValues('tag'); // remove not exists tags
        $model->save();
        $this->specify("after delete not exist tag nothing changed", function () use ($model){
            expect("model attribute `tag` must content added tags", $model->tags)->equals('tag1,tag2');
            expect("we must find link on tags", yii\helpers\ArrayHelper::getColumn($model->getTagsList()->orderBy('text')->all(), 'text'))->equals(['tag1','tag2']);
        });

        $model->removeTagValues('tag1');
        $model->save();
        $this->specify("after delete exist tag", function () use ($model){
            expect("model attribute `tag` must content one tags", $model->tags)->equals('tag2');
            expect("we must find link on only one tags", yii\helpers\ArrayHelper::getColumn($model->getTagsList()->orderBy('text')->all(), 'text'))->equals(['tag2']);
        });

        $model->removeAllTagValues();
        expect("model without tags must be save", $model->save())->true();
        $this->specify("after save model can't have tags", function () use ($model){
            expect("model attribute `tag` must be empty", $model->tags)->isEmpty();
            $tagsList = $model->getTagsList()->orderBy('text')->all();
            expect("we can't find link on tags", yii\helpers\ArrayHelper::getColumn($tagsList, 'text'))->isEmpty();
            expect("tags count must be 0", yii\helpers\ArrayHelper::getColumn(Tag::find()->all(), 'count'))->equals([0,0]);
        });

        $model->addTags('tag3');
        $model->save();
        $this->specify("after save new model with tags we must view tags", function () use ($model){
            expect("model attribute `tag` must content added tags", $model->tags)->equals('tag3');
            $tagsList = $model->getTagsList()->orderBy('text')->all();
            expect("we must find link on tags", yii\helpers\ArrayHelper::getColumn($tagsList, 'text'))->equals(['tag3']);
            expect("tags count must be equal", yii\helpers\ArrayHelper::getColumn($tagsList, 'count'))->equals([1]);
            expect("we must get tags by behavior", $model->getTagValues())->equals(['tag3']);
        });

    }

    protected function tearDown()
    {
        parent::tearDown();
    }
}
