<?php
namespace common\components\zContractParser;

use yii\base\Exception;

class zContractParser
{
    const DFT_NAMESPACE = 'http://zakupki.gov.ru/oos/types/1';
    const SUPPLIERS_XPATH = '//dft:supplier';
    const PRODUCT_XPATH = '//dft:products/dft:product';
    const OKPD_CODE_BUILDING = 45;

    public $xmlString;
    /** @var \SimpleXMLElement */
    public $xml;
    public $okpd = [];
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

    public function getOKPD()
    {
        if (!$this->isReady()) {
            return false;
        }
        $products = $this->xml->xpath(self::PRODUCT_XPATH);
        if (count($products) < 1) {
            return false;
        }
        $result = [];
        foreach ($products as $product) {
            $okpd = (string)$product->OKPD->code;
            $result[] = $okpd;
        }
        $result = array_keys(array_flip($result)); // оставит только уникальные коды
        $this->okpd = $result;

        return $result;
    }
    public function isBuilding()
    {
        if (empty($this->okpd)) {
            $this->getOKPD();
        }
        $okpds = $this->okpd;
        foreach ($okpds as $okpd) {
            $exploded = explode('.',$okpd);
            if (empty($exploded)) {
                continue;
            }
            if ($exploded[0] == self::OKPD_CODE_BUILDING) {
                return true;
            }
        }

        return false;
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