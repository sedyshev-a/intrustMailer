<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mailgun_keys".
 *
 * @property integer $id
 * @property string $api_key
 * @property string $pub_key
 * @property integer $banned
 * @property integer $locked
 * @property string $login
 */
class MailgunKeys extends \yii\db\ActiveRecord
{
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
            [['banned', 'locked'], 'integer'],
            [['api_key', 'pub_key'], 'string', 'max' => 150],
            [['login'], 'string', 'max' => 100],
            [['api_key'], 'unique'],
            [['login'], 'unique']
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
            'pub_key' => 'Pub Key',
            'banned' => 'Banned',
            'locked' => 'Locked',
            'login' => 'Login',
        ];
    }

    /**
     * @inheritdoc
     * @return MailgunKeysQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new MailgunKeysQuery(get_called_class());
    }

    /**
     * @return MailgunKeys|null
     */
    public static function getGoodAccount()
    {
        return self::find()
            ->available()
            ->orderBy('id')
            ->limit(1)
            ->one();
    }

    public function lock()
    {
        $this->locked = true;
        $this->save();
    }

    public function unlock()
    {
        $this->locked = false;
        $this->save();
    }

    public function ban()
    {
        $this->banned = true;
        $this->save();
    }

    public function unban()
    {
        $this->banned = false;
        $this->save();
    }
}
