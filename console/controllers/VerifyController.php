<?php
namespace console\controllers;

use common\components\MailgunUtils;
use common\models\Contacts;
use Yii;
use yii\base\InvalidParamException;
use yii\console\Controller;
use yii\console\Exception;

class VerifyController extends Controller
{
    public $defaultAction = 'verify';

    public function init()
    {
        parent::init();
    }

    public function actionVerify()
    {
        $emails = Contacts::getVerifyingNeededEmails(50);
        if (count($emails) < 1) {
            print 'Nothing to send!' . PHP_EOL;
            return Controller::EXIT_CODE_NORMAL;
        }


        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionValidate()
    {
        $mailgun = new MailgunUtils();
        $e = Contacts::getNewEmails(50);
        $emails = [];
        foreach ($e as $item) {
            $emails[] = $item['email'];
        }
        $totalValid = $totalInvalid = 0;
        while (count($emails) > 0) {
            $res = $mailgun->validate($emails);

            $updated = Contacts::updateAll(['emailStage' => Contacts::EMAIL_STAGE_SYNTAX_CHECKED],
                ['in','email',$res->parsed]);
            $valid = count($res->parsed);
            print "Valid: $valid" . PHP_EOL;
            print "Updated valid: $updated" . PHP_EOL . PHP_EOL;

            $invalid = count($res->unparseable);
            if ($invalid > 0) {
                $updated = Contacts::updateAll(['emailStage' => Contacts::EMAIL_STAGE_INVALID],
                    ['in','email',$res->unparseable]);
                print "Invalid: $invalid" . PHP_EOL;
                print "Updated invalid: $updated" . PHP_EOL . PHP_EOL;
            }

            $totalValid += $valid; $totalInvalid += $invalid;
            print '------------------------------------------------------------' . PHP_EOL;
            print "Total valid: $totalValid | Total invalid: $totalInvalid" . PHP_EOL . PHP_EOL . PHP_EOL;

            $e = Contacts::getNewEmails(50);
            $emails = [];
            foreach ($e as $item) {
                $emails[] = $item['email'];
            }
        }



        return Controller::EXIT_CODE_NORMAL;
    }
}