<?php
namespace common\components\zContractParser;

use yii\base\Exception;
use yii\helpers\VarDumper;

class ipRF extends AbstractSupplier
{

    public function getInfo()
    {
        $xml = $this->xml->children();
        $this->setFullName($this->buildName());
        $this->setRegDate($xml->registrationDate);
        $this->setInn($xml->INN);
        $this->setAddress($xml->address);
        $this->setPhone($xml->contactPhone);

        $emails = $this->extractEmails($xml->contactEMail);
        $emails = $this->filterEmails($emails);
        $this->setEmails($emails);

        return $this->toArray();
    }

    private function buildName()
    {
        $xml = $this->xml->children();
        $lastName = trim((string)$xml->lastName);
        $firstName = trim((string)$xml->firstName);
        $middleName = trim((string)$xml->middleName);
        if (($lastName === $firstName) && !empty($lastName)) {
            return $lastName;
        }

        $result = $lastName;
        if (!empty($firstName)) {
            $result .= " {$firstName}";
        }
        if (!empty($middleName)) {
            $result .= " $middleName";
        }

        return $result;
    }
}