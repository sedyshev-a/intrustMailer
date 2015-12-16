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

    public function actionVerify($batchCount = 50)
    {
        $emails = Contacts::getVerifyingNeededEmails($batchCount);
        if (count($emails) < 1) {
            print 'Nothing to verify!' . PHP_EOL;
            return Controller::EXIT_CODE_NORMAL;
        }
        try {
            $mailgun = new MailgunUtils();
        } catch (Exception $e) {
            print $e->getMessage();
            return Controller::EXIT_CODE_ERROR;
        }
        $mailgunAccount = $mailgun->getAccount();
        $mailgunAccount->lock();



        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionValidate()
    {
        try {
            $mailgun = new MailgunUtils();
        } catch (Exception $e) {
            print $e->getMessage();
            return Controller::EXIT_CODE_ERROR;
        }
        $emails = Contacts::getNewEmails(50);
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
                print_r($res->unparseable);
                print "Updated invalid: $updated" . PHP_EOL . PHP_EOL;
            }

            $totalValid += $valid; $totalInvalid += $invalid;
            print '------------------------------------------------------------' . PHP_EOL;
            print "Total valid: $totalValid | Total invalid: $totalInvalid" . PHP_EOL . PHP_EOL . PHP_EOL;

            $emails = Contacts::getNewEmails(50);
        }

        return Controller::EXIT_CODE_NORMAL;
    }
}