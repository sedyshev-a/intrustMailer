<?php
namespace console\controllers;

use common\components\Ftp;
use Yii;
use yii\base\Exception;
use yii\base\ErrorException;
use yii\helpers\BaseFileHelper;
use yii\console\Controller;

class LoadController extends Controller
{
    protected $tempDir = 'temp_archives';
    private $contractsRemotePath;
    private $filter;

    public function init()
    {
        $this->contractsRemotePath = Yii::$app->params['zakupki']['contractsPath'];
    }
    private function load()
    {
        $ftpParams =  Yii::$app->params['zakupki']['ftp'];
        $excludedRegionDirs = Yii::$app->params['zakupki']['excludedRegionDirs'];
        $path = Yii::$app->getRuntimePath() . '/archives';
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
            $files = $ftp->lsFiles("{$region}{$this->contractsRemotePath}", 22);
            foreach ($files as $file) {
                if (strpos($file,"{$region}_{$this->filter}") === false) {
                    continue;
                }
                if (file_exists($path .'/'. basename($file))) {
                    continue;
                }
                echo "try to get '{$file}'...";
                $result = $ftp->get("{$region}{$this->contractsRemotePath}/{$file}");
                if ($result) {
                    echo " Success!" . PHP_EOL;
                }
                else {
                    echo " Fail!" . PHP_EOL;
                    echo $ftp->getLastError() . PHP_EOL;
                    continue;
                }
            }
        }
        $ftp->disconnect();
        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionYearBatch()
    {
        $this->filter = '2015';
        $this->load();
    }

    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function actionMonthBatch($month)
    {
        $month = (int)$month;
        if ($month > 12 || $month < 1) {
            print 'Incorrect month' . PHP_EOL;
            return Controller::EXIT_CODE_ERROR;
        }
        $month = sprintf('%02d', $month);
        $this->filter = "2015{$month}";
        $this->load();
    }

    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function actionMonth($month)
    {
        $month = (int)$month;
        $currentMonth = (int)date('n');
        print $currentMonth . PHP_EOL;
        if ($month > 12 || $month < 1) {
            print 'Incorrect month' . PHP_EOL;
            return Controller::EXIT_CODE_ERROR;
        }
        if ($month > $currentMonth) {
            print 'Month can\'t be greater than the current' . PHP_EOL;
            return Controller::EXIT_CODE_ERROR;
        }
        $month = sprintf('%02d', $month);
        $this->filter = "2015{$month}";
        $this->contractsRemotePath = ($month === $currentMonth) ? '/contracts/currMonth' : '/contracts/prevMonth';
        $this->load();
    }

}