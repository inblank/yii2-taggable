<?php

namespace app\models;

use yii\db\ActiveRecord;

class Tag extends ActiveRecord
{

    public static function tableName()
    {
        return 'tag';
    }

    public function rules()
    {
        return [
            ['text', 'required'],
            ['text', 'string', 'max' => 250],
            ['count', 'integer'],
            ['count', 'default', 'value' => 0],
        ];
    }
}
