<?php
namespace console\controllers;

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
        $emails = Contacts::getVerifyingNeededEmails();
        if (count($emails) < 1) {

        }
    }
}