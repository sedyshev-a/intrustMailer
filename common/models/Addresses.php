<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "addresses".
 *
 * @property integer $id
 * @property string $address
 * @property integer $orgId
 *
 * @property Organizations $org
 */
class Addresses extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'addresses';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['orgId'], 'required'],
            [['orgId'], 'integer'],
            [['address'], 'string', 'max' => 150],
            [['address', 'orgId'], 'unique', 'targetAttribute' => ['address', 'orgId'], 'message' => 'The combination of Address and Org ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'address' => 'Address',
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
