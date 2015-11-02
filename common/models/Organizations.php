<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "organizations".
 *
 * @property integer $id
 * @property integer $type
 * @property string $regDate
 * @property string $actualDate
 * @property string $inn
 * @property string $kpp
 * @property string $fullName
 * @property string $shortName
 * @property string $firmName
 * @property integer $isBuilder
 *
 * @property Addresses[] $addresses
 * @property Contacts[] $contacts
 * @property Okveds[] $okveds
 */
class Organizations extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'organizations';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'isBuilder'], 'integer'],
            [['regDate', 'actualDate'], 'safe'],
            [['inn', 'fullName'], 'required'],
            [['inn'], 'string', 'max' => 12],
            [['kpp'], 'string', 'max' => 9],
            [['fullName'], 'string', 'max' => 500],
            [['shortName', 'firmName'], 'string', 'max' => 250],
            [['inn'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'regDate' => 'Reg Date',
            'actualDate' => 'Actual Date',
            'inn' => 'Inn',
            'kpp' => 'Kpp',
            'fullName' => 'Full Name',
            'shortName' => 'Short Name',
            'firmName' => 'Firm Name',
            'isBuilder' => 'Is Builder',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddresses()
    {
        return $this->hasMany(Addresses::className(), ['orgId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContacts()
    {
        return $this->hasMany(Contacts::className(), ['orgId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOkveds()
    {
        return $this->hasMany(Okveds::className(), ['orgId' => 'id']);
    }
}
