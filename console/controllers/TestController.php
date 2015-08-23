<?php
namespace console\controllers;

use common\components\Ftp;
use Yii;
use yii\base\Exception;
use yii\base\ErrorException;
use yii\console\Controller;

class TestController extends Controller
{
    public function actionIndex($msg = 'default message')
    {
        $ftpParams =  Yii::$app->params['zakupki']['ftp'];
        $excludedRegionDirs = Yii::$app->params['zakupki']['excludedRegionDirs'];

        $ftp = new Ftp([
            'host' => $ftpParams['host'],
            'port' => $ftpParams['port'],
            'username' => $ftpParams['username'],
            'password' => $ftpParams['password'],
            'passiveMode' => true,
            'timeout' => 5,
        ]);
        if (!$ftp->connect() || !$ftp->login()) {
            echo $ftp->getLastError() . PHP_EOL;
            return Controller::EXIT_CODE_ERROR;
        }
        $ftp->chDir('fcs_regions');
        $dirs = $ftp->lsDir();
        $dirs = array_filter($dirs, function($value) use ($excludedRegionDirs){
            return !in_array($value, $excludedRegionDirs);
        });
        foreach ($dirs as $region) {
            $files = $ftp->lsFiles("{$region}/contracts");
            //print_r($files);
        }
        echo Yii::$app->getRuntimePath() . PHP_EOL;
        $ftp->disconnect();
        return Controller::EXIT_CODE_NORMAL;
    }
}