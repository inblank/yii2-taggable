<?php

namespace app\models;

use inblank\taggable\TaggableBehavior;
use yii\db\ActiveRecord;

/**
 * Test model class without tags link
 *
 * @property int $id
 * @property string $title
 * @property string $tags
 *
 * @method setTagValues($tags)
 *
 * @package app\models
 */
class Model2 extends ActiveRecord
{

    public static function tableName()
    {
        return 'model2';
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

}
