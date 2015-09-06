<?php
namespace common\components\zContractParser;

use yii\base\Exception;
use yii\helpers\VarDumper;

class LegalEntityRFSupplier extends AbstractSupplier
{

    public function getInfo()
    {
        $xml = $this->xml->children();
        $this->setType($xml->legalForm->code);
        $this->setFullName($xml->fullName);
        $this->setShortName($xml->shortName);
        $this->setFirmName($xml->firmName);
        $this->setRegDate($xml->registrationDate);

        $this->setInn($xml->INN);
        $this->setKpp($xml->KPP);
        $this->setAddress($xml->address);
        $this->setPhone($xml->contactPhone);

        $debug = [];
        $debug['raw'] = (string)$xml->contactEMail;

        $emails = $this->extractEmails($xml->contactEMail);
        $debug['extracted'] = $emails;
        $emails = $this->filterEmails($emails);
        $debug['filtered'] = $emails;

        file_put_contents(\Yii::$app->getRuntimePath() . '/emails.log', print_r($debug, true), FILE_APPEND);

        $this->setEmails($emails);

        $this->setContactLastName($xml->contactInfo->lastName);
        $this->setContactFirstName($xml->contactInfo->firstName);

        return $this->toArray();
    }
}