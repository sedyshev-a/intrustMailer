<?php
namespace console\controllers;

use common\components\Ftp;
use Yii;
use yii\base\Exception;
use yii\base\ErrorException;
use yii\helpers\BaseFileHelper;
use yii\console\Controller;

class TestController extends Controller
{
    protected $tempDir = 'temp_archives';

    public function actionIndex($msg = 'default message')
    {
        $ftpParams =  Yii::$app->params['zakupki']['ftp'];
        $excludedRegionDirs = Yii::$app->params['zakupki']['excludedRegionDirs'];
        $path = Yii::$app->getRuntimePath() . '/archives';
        //BaseFileHelper::removeDirectory($path);
        BaseFileHelper::createDirectory($path);

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
        $ftp->setTimeout(45);
        $ftp->localDir = $path;
        $ftp->chDir('fcs_regions');
        $dirs = $ftp->lsDir();
        $dirs = array_filter($dirs, function($value) use ($excludedRegionDirs){
            return !in_array($value, $excludedRegionDirs);
        });
        foreach ($dirs as $region) {
            $files = $ftp->lsFiles("{$region}/contracts");
            foreach ($files as $file) {
                if (strpos($file,"{$region}_2015") === false) {
                    continue;
                }
                if (file_exists($path .'/'. basename($file))) {
                    continue;
                }
                echo "try to get '{$file}'...";
                $result = $ftp->get("{$region}/contracts/{$file}");
                if ($result) {
                    echo " Success!" . PHP_EOL;
                }
                else {
                    echo " Fail!" . PHP_EOL;
                    echo $ftp->getLastError() . PHP_EOL;
                    break(2);
                }
            }
        }
        $ftp->disconnect();
        return Controller::EXIT_CODE_NORMAL;
    }
}