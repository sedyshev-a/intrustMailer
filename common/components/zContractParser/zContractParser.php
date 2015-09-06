<?php
namespace common\components\zContractParser;

use yii\base\Exception;
use yii\helpers\VarDumper;

class zContractParser
{
    const DFT_NAMESPACE = 'http://zakupki.gov.ru/oos/types/1';
    const SUPPLIERS_XPATH = '//dft:supplier';

    public $xmlString;
    /** @var \SimpleXMLElement */
    public $xml;

    protected $error = '';

    public function __construct($xmlString)
    {
        $this->setXml($xmlString);
    }
    protected function getSupplierParser($type, $supplierXml)
    {
        switch ($type) {
            case 'legalEntityRF':
                return new LegalEntityRFSupplier($supplierXml);
            case 'legalEntityForeignState':
                return new LegalEntityFSSupplier($supplierXml);
            case 'individualPersonRF':
                return new ipRF($supplierXml);
            case 'individualPersonForeignState':
                return new ipFS($supplierXml);
            default:
                return false;
        }
    }
    public function getSuppliersInfo()
    {
        if (!$this->isReady()) {
            return false;
        }
        $suppliers = $this->xml->xpath(self::SUPPLIERS_XPATH);
        if (count($suppliers) < 1) {
            return false;
        }
        $result = [];
        foreach ($suppliers as $supplier) {
            $legalForm = $supplier->children();
            $legalFormName = $legalForm->getName();
            /** @var AbstractSupplier $parser */
            $parser = $this->getSupplierParser($legalFormName, $legalForm);
            if ($parser === false) {
                continue;
            }
            $info = $parser->getValidInfo();
            if ($info === false) {
                continue;
            }
            $result[] = $info;
        }
        return $result;
    }


    public function setXml($xmlString)
    {
        $this->xmlString = $xmlString;
        try {
            $this->xml = new \SimpleXMLElement($xmlString);
        } catch (Exception $e) {
            $this->xml = null;
            $this->setError($e->getMessage());
        }
        if ($this->isReady()) {
            $this->xml->registerXPathNamespace('dft', self::DFT_NAMESPACE);
        }
    }
    public function getXml()
    {
        return $this->xml;
    }
    public function isReady()
    {
        return $this->xml instanceof \SimpleXMLElement;
    }
    protected function setError($msg)
    {
        $this->error = $msg;
    }
    public function getLastError()
    {
        return $this->error;
    }

}