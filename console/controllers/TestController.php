<?php
namespace console\controllers;


use common\components\MailgunUtils;
use common\models\Organizations;
use Yii;
use yii\base\Exception;
use yii\base\ErrorException;
use yii\helpers\BaseFileHelper;
use yii\console\Controller;
use common\components\Ftp;
use common\components\PostRequest;

class TestController extends Controller
{

    public function actionTest()
    {
        $sql = <<< SQL
SELECT o.fullName, o.shortName, o.firmName, c.email FROM yii2advanced.organizations o
INNER JOIN yii2advanced.contacts c ON o.id = c.orgId
WHERE (o.`type` DIV 100) in (121)
	AND o.isBuilder=1
    AND c.emailStage > 0
ORDER BY o.inn
LIMIT 9000;
SQL;
        $orgs = Yii::$app->db->createCommand($sql)->queryAll();

        $recipients = []; $recipientVars = [];
        $i = 0;
        foreach ($orgs as $org) {
            if (!empty($org['shortName'])) {
                $name = $org['shortName'];
            } elseif (!empty($org['fullName'])) {
                $name = $org['fullName'];
            } else {
                continue;
            }
            $email = $org['email'];
            if (isset($recipientVars[$i][$email])) {
                print $email . PHP_EOL;
                continue;
            }

            $recipients[$i][] = $email;
            $recipientVars[$i][$email]['name'] = $name;
            if (count($recipients[$i]) === 1000) {
                $i++;
            }
        }
        $mailgun = new MailgunUtils();

        $results = [];
        foreach ($recipients as $key => $chunk) {
            $results[] = $mailgun->send($chunk, $recipientVars[$key]);
        }
        echo "success" . PHP_EOL;
        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionEvents()
    {
        $MSK = new \DateTimeZone('Europe/Moscow');
        $begin = new \DateTime('now', $MSK);
        $begin->setTime(19,0,0);
        $end = clone $begin;
        $end->setTime(23,59,59);

        $mailgun = new MailgunUtils();
        $eventsDelivered = $mailgun->fetchEvents(['delivered']);

        foreach ($eventsDelivered as $item) {
            if (!isset($item['recipient'])) {
                continue;
            }

        }


        return Controller::EXIT_CODE_NORMAL;
    }

}