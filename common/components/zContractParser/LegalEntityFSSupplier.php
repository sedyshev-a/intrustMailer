<?php
namespace common\components\zContractParser;

use yii\base\Exception;
use yii\helpers\VarDumper;

class LegalEntityFSSupplier extends AbstractSupplier
{
    const RF_COUNTRY_CODE = '643';

    public function getInfo()
    {
        $xml = $this->xml->children();

        $inn = $this->extractINN();
        if ($inn === false || substr($inn,0,4) === '9909') {
            return false;
        }
        $this->setFullName($xml->fullName);
        $this->setShortName($xml->shortName);
        $this->setFirmName($xml->firmName);
        $this->setRegDate($xml->registerInRFTaxBodies->registrationDate);
        $this->setInn($inn);
        $this->setKpp($xml->registerInRFTaxBodies->KPP);
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
}