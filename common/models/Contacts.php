<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "contacts".
 *
 * @property integer $id
 * @property string $lastName
 * @property string $firstName
 * @property string $middleName
 * @property string $email
 * @property string $tel
 * @property integer $orgId
 * @property integer $emailStage
 *
 * @property Organizations $org
 */
class Contacts extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    const EMAIL_STAGE_INVALID = -1;
    const EMAIL_STAGE_NEW = 0;
    const EMAIL_STAGE_SYNTAX_CHECKED = 1;
    const EMAIL_STAGE_FULL_VALID = 3;



    public static function tableName()
    {
        return 'contacts';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['orgId'], 'required'],
            [['orgId', 'emailStage'], 'integer'],
            [['lastName', 'firstName', 'middleName', 'email', 'tel'], 'string', 'max' => 45],
            [['email', 'orgId'], 'unique', 'targetAttribute' => ['email', 'orgId'], 'message' => 'The combination of Email and Org ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'lastName' => 'Last Name',
            'firstName' => 'First Name',
            'middleName' => 'Middle Name',
            'email' => 'Email',
            'tel' => 'Tel',
            'orgId' => 'Org ID',
            'emailStage' => 'Email Stage',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrg()
    {
        return $this->hasOne(Organizations::className(), ['id' => 'orgId']);
    }

    /**
     * @param int $limit
     * @return array|Contacts[]
     */
    public static function getVerifyingNeededEmails($limit = 1000)
    {
        $emails = self::find()
            ->select('email')
            ->distinct()
            ->where(['emailStage' => [self::EMAIL_STAGE_SYNTAX_CHECKED]])
            ->limit($limit)
            ->asArray()
            ->all();

        return $emails;
    }

    /**
     * @param int $limit
     * @return array|Contacts[]
     */
    public static function getNewEmails($limit = 1000)
    {
        $emails = self::find()
            ->select('email')
            ->distinct()
            ->where(['emailStage' => [self::EMAIL_STAGE_NEW]])
            ->limit($limit)
            ->asArray()
            ->all();
        $result = [];
        foreach ($emails as $email) {
            $result[] = $email['email'];
        }
        return $result;
    }
}
