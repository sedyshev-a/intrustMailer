<?php
namespace common\components;

use Yii;
use yii\base\InvalidParamException;


class MailSyntaxValidator
{
    private $emails = [];

    /**
     * @return array
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * @param array|string $emails
     */
    public function setEmails($emails)
    {
        $this->emails = [];
        if (is_array($emails)) {
            $this->emails = $emails;
            return;
        }
        if (is_string($emails)) {
            $this->emails[] = $emails;
            return;
        }
        throw new InvalidParamException('Emails must be a string or array of string!');
    }

    public function __construct($emails)
    {
        if (isset($emails)) {
            $this->setEmails($emails);
        }
    }


}