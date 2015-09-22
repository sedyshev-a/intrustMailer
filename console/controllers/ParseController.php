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
        $pdo = Yii::$app->db->pdo;
        $orgSQL = <<<SQL
INSERT INTO organizations (type, regDate, actualDate, inn, kpp, fullName, shortName, firmName, isBulder)
VALUES (:type, :regDate, :actualDate, :inn, :kpp, :fullName, :shortName, :firmName, :isBuilder)
ON DUPLICATE KEY UPDATE
  type = IF((:actualDate > actualDate), :type, type),
  kpp = IF((:actualDate > actualDate), :kpp, kpp),
  fullName = IF((:actualDate > actualDate), :fullName, fullName),
  shortName = IF((:actualDate > actualDate), :shortName, shortName),
  firmName = IF((:actualDate > actualDate), :firmName, firmName)
SQL;

        $orgStatement = $pdo->prepare($orgSQL);

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

            $numFiles = $zip->numFiles;
            for ($i=0; $i<$numFiles; $i++) {

                $xmlFilename = $zip->getNameIndex($i);
                $type = explode('_',$xmlFilename)[0];
                if ($type !== 'contract') {
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