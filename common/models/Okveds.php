<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "okveds".
 *
 * @property integer $id
 * @property string $okved
 * @property integer $orgId
 *
 * @property Organizations $org
 */
class Okveds extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'okveds';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['okved', 'orgId'], 'required'],
            [['orgId'], 'integer'],
            [['okved'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'okved' => 'Okved',
            'orgId' => 'Org ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrg()
    {
        return $this->hasOne(Organizations::className(), ['id' => 'orgId']);
    }
}
