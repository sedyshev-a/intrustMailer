<?php
namespace console\controllers;


use Yii;
use yii\base\Exception;
use yii\base\ErrorException;
use yii\helpers\BaseFileHelper;
use yii\console\Controller;
use common\components\Ftp;
use common\components\PostRequest;

class TestController extends Controller
{

    public function actionVote()
    {
        $proxyUrl = 'http://www.freeproxy-list.ru/api/proxy?anonymity=false&count=1000&token=1ebb323f4e5e44678813f0f8a72f6946';
        $content = file_get_contents($proxyUrl);
        $rawList = explode("\n",$content);
        $list = [];
        foreach ($rawList as $item) {
            $tmp = explode(':', $item);
            $list[] = ['ip' => $tmp[0], 'port' => $tmp[1]];
        }
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'x-requested-with: XMLHttpRequest'
        ];
        $requestOptions = [
            CURLOPT_POST           => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 7,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => http_build_query([
                'qid' => 3,
                'oid' => 36,
                'xp_action' => 'answer'
            ]),
        ];
        $ch = curl_init('http://ivolga-missnn.ru/');
        curl_setopt_array($ch, $requestOptions);
        shuffle($list);
        foreach ($list as $item) {
            curl_setopt($ch, CURLOPT_PROXY, $item['ip']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $item['port']);
            $response = curl_exec($ch);
            if ($response) {
                echo 'voted!' . PHP_EOL;
            }
            else {
                echo 'proxy error!' . PHP_EOL;
            }
        }

        curl_close($ch);
        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionVote2() {
        $proxyUrl = 'http://www.freeproxy-list.ru/api/proxy?anonymity=false&count=1000&token=1ebb323f4e5e44678813f0f8a72f6946';
        $content = file_get_contents($proxyUrl);
        $rawList = explode("\n",$content);
        $list = [];
        foreach ($rawList as $item) {
            $tmp = explode(':', $item);
            $list[] = ['ip' => $tmp[0], 'port' => $tmp[1]];
        }
        shuffle($list);
        $url = 'http://www.nnov.kp.ru/daily/forumcontest/photo/172006/vote?geoid=1&view=desktop';
        $headers = [
            'X-Requested-With: XMLHttpRequest',
            'X-Prototype-Version: 1.7.2',
            'Accept: text/javascript, text/html, application/xml, text/xml, */*',
        ];
        $requestOptions = [
            //CURLOPT_POST           => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 7,
            CURLOPT_HTTPHEADER     => $headers,

        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, $requestOptions);
        $proxyIndex = 0; $nextProxy = true;
        for ($i=0;$i<1000;$i++) {
            if ($nextProxy) {
                if (!isset($list[$proxyIndex])) {
                    $proxyIndex = 0;
                }
                curl_setopt($ch, CURLOPT_PROXY, $list[$proxyIndex]['ip']);
                curl_setopt($ch, CURLOPT_PROXYPORT, $list[$proxyIndex]['port']);
                $nextProxy = false;
            }
            $response = curl_exec($ch);
            if ($response) {
                echo $i . ' success!' . PHP_EOL;
            }
            else {
                echo 'proxy error!' . PHP_EOL;
                $nextProxy = true;
                $proxyIndex++;
            }
        }
        curl_close($ch);

    }

}