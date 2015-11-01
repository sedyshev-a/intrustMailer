<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mailgun_keys".
 *
 * @property integer $id
 * @property string $api_key
 * @property string $domain
 * @property string $status
 */
class MailgunKeys extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mailgun_keys';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['api_key'], 'required'],
            [['api_key', 'domain', 'status'], 'string', 'max' => 45],
            [['api_key'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'api_key' => 'Api Key',
            'domain' => 'Domain',
            'status' => 'Status',
        ];
    }
}
