<?php
namespace common\components\zContractParser;

use yii\base\Exception;
use yii\helpers\VarDumper;

class ipFS extends AbstractSupplier
{
    const RF_COUNTRY_CODE = '643';

    public function getInfo()
    {
        $xml = $this->xml->children();

        $inn = $this->extractINN();
        if ($inn === false) {
            return false;
        }
        $this->setFullName($this->buildName());
        $this->setRegDate($xml->registerInRFTaxBodies->registrationDate);
        $this->setInn($inn);
        $this->setAddress($xml->placeOfStayInRegCountry->address);
        $this->setPhone($xml->placeOfStayInRegCountry->contactPhone);

        $emails = $this->extractEmails($xml->placeOfStayInRegCountry->contactEMail);
        $emails = $this->filterEmails($emails);
        $this->setEmails($emails);

        return $this->toArray();
    }

    private function extractINN()
    {
        $xml = $this->xml->children();
        $country = (string)$xml->placeOfStayInRegCountry->country->countryCode;
        if ($country != self::RF_COUNTRY_CODE) {
            return false;
        }
        $inn = (string)$xml->registerInRFTaxBodies->INN;
        if (empty($inn)) {
            $inn = (string)$xml->taxPayerCode;
        }
        if (empty($inn)) {
            return false;
        }

        return $inn;
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