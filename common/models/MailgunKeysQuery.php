<?php

namespace common\models;

/**
 * This is the ActiveQuery class for [[MailgunKeys]].
 *
 * @see MailgunKeys
 */
class MailgunKeysQuery extends \yii\db\ActiveQuery
{
    public function available()
    {
        $this->andWhere([
            'banned' => 0,
            'locked' => 0,
        ]);
        return $this;
    }

    /**
     * @inheritdoc
     * @return MailgunKeys[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return MailgunKeys|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}