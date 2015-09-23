<?php
namespace console\controllers;

use common\components\zContractParser\zContractParser;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\helpers\BaseFileHelper;
use yii\console\Controller;

class ParseController extends Controller
{
    protected $tempDir = 'temp_archives';

    public function actionIndex()
    {
        $path = Yii::$app->getRuntimePath() . '/archives';

        $archivesList = BaseFileHelper::findFiles($path,[
            'only' => ['*.zip'],
            'recursive' => false,
        ]);
        $zip = new \ZipArchive();

        $type = NULL;
        $regDate = NULL;
        $actualDate = NULL;
        $inn = NULL;
        $kpp = NULL;
        $fullName = NULL;
        $shortName = NULL;
        $firmName = NULL;
        $isBuilder = NULL;
        $pdo = Yii::$app->db->getMasterPdo();
        var_dump($pdo);

        $orgSQL = <<<SQL
INSERT INTO organizations (type, regDate, actualDate, inn, kpp, fullName, shortName, firmName, isBulder)
VALUES (:type, :regDate, :actualDate, :inn, :kpp, :fullName, :shortName, :firmName, :isBuilder)
ON DUPLICATE KEY UPDATE
  type       = IF((:actualDate > actualDate), :type, type),
  kpp        = IF((:actualDate > actualDate), :kpp, kpp),
  fullName   = IF((:actualDate > actualDate), :fullName, fullName),
  shortName  = IF((:actualDate > actualDate), :shortName, shortName),
  firmName   = IF((:actualDate > actualDate), :firmName, firmName),
  actualDate = IF((:actualDate > actualDate), :actualDate, actualDate),
  id         = LAST_INSERT_ID(id);
SQL;

        $orgStatement = $pdo->prepare($orgSQL);
        $orgStatement->bindParam(':type',$type);
        $orgStatement->bindParam(':regDate',$regDate);
        $orgStatement->bindParam(':actualDate',$actualDate);
        $orgStatement->bindParam(':inn',$inn);
        $orgStatement->bindParam(':kpp',$kpp);
        $orgStatement->bindParam(':fullName',$fullName);
        $orgStatement->bindParam(':firmName',$firmName);
        $orgStatement->bindParam(':isBuilder',$isBuilder);

        $contactsSQL = <<<SQL
INSERT IGNORE INTO contacts (orgId, lastName, firstName, middleName, email, tel, emailStage)
VALUES (:orgId, :lastName, :lastName, :firstName, :middleName, :email, :tel);
SQL;
        $orgId = null;
        $lastName = null;
        $firstName = null;
        $middleName = null;
        $email = null;
        $tel = null;
        $contactsStatement = $pdo->prepare($contactsSQL);
        $contactsStatement->bindParam(':orgId', $orgId);
        $contactsStatement->bindParam(':lastName', $lastName);
        $contactsStatement->bindParam(':firstName', $firstName);
        $contactsStatement->bindParam(':middleName', $middleName);
        $contactsStatement->bindParam(':email', $email);
        $contactsStatement->bindParam(':tel', $tel);
        $contactsStatement->bindValue(':emailStage', 1);
        $contactsStatement->bindParam(':orgId', $orgId);

        $addrSQL = <<<SQL
INSERT IGNORE INTO addresses (orgId, address)
VALUES (:orgId, :address);
SQL;
        $address = null;
        $addrStatement = $pdo->prepare($addrSQL);
        $addrStatement->bindParam(':orgId', $orgId);
        $addrStatement->bindParam(':address', $address);

        foreach ($archivesList as $archivePath) {
            try {
                $zipOpened = $zip->open($archivePath);
            } catch (ErrorException $e) {
                print $e->getMessage() . PHP_EOL;
                continue;
            }
            if ($zipOpened !== true) {
                continue;
            }
            $actualDate = $this->extractActualDate(basename($archivePath));
            if (!($actualDate instanceof \DateTime)) {
                throw new Exception('Can\'t extract actualDate');
            }
            $actualDate = $actualDate->format('Y-m-d');
            $numFiles = $zip->numFiles;
            for ($i=0; $i<$numFiles; $i++) {

                $xmlFilename = $zip->getNameIndex($i);
                $fileType = explode('_',$xmlFilename)[0];
                if ($fileType !== 'contract') {
                    continue;
                }

                $contractParser = new zContractParser($zip->getFromIndex($i));
                $isBuilder = $contractParser->isBuilding();

                $suppliers = $contractParser->getSuppliersInfo();
                if ($suppliers === false || count($suppliers) < 1) {
//                    print '---------------' . $xmlFilename . '---------------'.PHP_EOL;
//                    print 'No suppliers: ' . basename($archivePath) . PHP_EOL;
//                    print '-----------------------' . PHP_EOL;
                    continue;
                }
                print $type . PHP_EOL;
                foreach ($suppliers as $supplier) {
                    $type = $supplier['type'];
                    $regDate = $supplier['regDate'];
                    $inn = $supplier['inn'];
                    $kpp = $supplier['kpp'];
                    $fullName = $supplier['fullName'];
                    $shortName = $supplier['shortName'];
                    $firmName = $supplier['firmName'];
                    $isBuilder = (int)$isBuilder;
                }
                print $type . PHP_EOL;
            }
        }
        return Controller::EXIT_CODE_NORMAL;
    }

    private function extractActualDate($filename)
    {
        $result = preg_match("/.*?(\\d+)/i",$filename,$matches);

        if ($result === false || $result === 0) {
            return false;
        }
        $strDate = $matches[1];
        $date = \DateTime::createFromFormat('Ymd??',$strDate);

        return $date;
    }
}