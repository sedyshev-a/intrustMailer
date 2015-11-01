<?php
namespace console\controllers;


use common\models\MailgunKeys;
use Mailgun\Mailgun;
use Yii;
use yii\base\InvalidParamException;
use yii\console\Controller;
use yii\console\Exception;

class MailgunController extends Controller
{

    /** @var Mailgun */
    protected $mailgun;
    protected $domain;

    protected static $eventTypes = [
        'accepted'     => 'accepted',
        'rejected'     => 'rejected',
        'delivered'    => 'delivered',
        'failed'       => 'failed',
        'opened'       => 'opened',
        'clicked'      => 'clicked',
        'unsubscribed' => 'unsubscribed',
        'complained'   => 'complained',
        'stored'       => 'stored',
    ];

    public function init()
    {
        /** @var MailgunKeys $keyItem */
        $keyItem = MailgunKeys::findOne([
            'status' => MailgunKeys::STATUS_ACTIVE
        ]);
        if (is_null($keyItem)) {
            throw new Exception("No active mailgun accounts!");
        }
        $this->mailgun = new Mailgun($keyItem->api_key);
        $domains = $this->fetchDomains();
        if (isset ($domains[0])) {
            $this->domain = $domains[0];
        }
        else {
            throw new Exception("No domains in mailgun account!");
        }
    }

    public function beforeAction($action) {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->checkRequiredAPIParams();
        return true;
    }

    public function checkRequiredAPIParams()
    {
        if (!($this->mailgun instanceof Mailgun)) {
            throw new Exception("Mailgun client not set!");
        }
        if (empty($this->domain) || !is_string($this->domain)) {
            throw new Exception("Mailgun domain not set!");
        }
    }

    public function actionIndex()
    {
        $emails = ['sed.yshev.a@gmail.com'];
        $recipientVars = []; $i = 0;
        foreach ($emails as $email) {
            $i++;
            $recipientVars[$email] = [
                'name' => "Имя-$i",
            ];
        }
        $recipientVars = json_encode($recipientVars);
        $result = $this->mailgun->sendMessage($this->domain, [
            'from'    => 'Писюнчик <manager@supercompany.com>',
            'to'      => implode(',',$emails),
            'subject' => 'Здесь должно быть имя: %recipient.name%',
            'text'    => 'Это тестовое письмо, отвечать на него не надо.
                          ПЫЩ ПЫЩ.',
            'recipient-variables' => $recipientVars,
        ]);

        print_r($result);
        return Controller::EXIT_CODE_NORMAL;
    }
    public function actionTest()
    {
        $MSK = new \DateTimeZone('Europe/Moscow');
        $begin = new \DateTime('now', $MSK);
        $begin->setTime(19,0,0);
        $end = clone $begin;
        $end->setTime(23,59,59);
        $items = $this->fetchEvents(['delivered']);
        print_r($items);

        return Controller::EXIT_CODE_NORMAL;
    }
    public function send()
    {

        return Controller::EXIT_CODE_NORMAL;
    }

    /**
     * @param array $eventList
     * @param \DateTime $begin
     * @param \DateTime $end
     * @param bool $ascending
     * @return array
     * @throws Exception
     */
    public function fetchEvents($eventList = [], $begin = null, $end = null, $ascending = true)
    {
        $this->checkRequiredAPIParams();
        $events = $eventList;
        foreach ($events as $key => $event) {
            if (!isset(self::$eventTypes[$event])) {
                Yii::warning("$event is not valid event!");
                unset($events[$key]);
                continue;
            }
            $events[$key] = self::$eventTypes[$event];
        }
        if (empty($events) && !empty($eventList)) {
            throw new Exception("No valid events passed to the method!");
        }
        $events = implode(' OR ', $events);
        print $events . PHP_EOL . PHP_EOL;
        $query = [
            'event' => $events,
        ];
        if (isset($begin)) {
            $query['begin'] = $begin->getTimestamp();
            if (isset($end)) {
                $query['end'] = $end->getTimestamp();
            } elseif (isset($ascending) && is_bool($ascending)) {
                $query['ascending'] = ($ascending) ? 'yes' : 'no' ;
            } else {
                throw new InvalidParamException('One of $end or $ascending params must be defined!');
            }
        }
        $body = $this->request("{$this->domain}/events", $query);
        $items = $body->items;
        do {
            $nextHash = $this->extractPageHash($body->paging->next);
            if (empty($nextHash)) {
                break;
            }
            $body = $this->request("{$this->domain}/events/$nextHash");
            $nextItems = $body->items;
            $items = array_merge($items, $nextItems);
            $countNextItems = count($nextItems);
        } while ($countNextItems > 0);

        return $items;
    }

    public function fetchDomains($sandboxOnly = false)
    {
        $body = $this->request("domains", ['limit' => 5]);
        $domainItems = $body->items;
        $result = [];
        foreach ($domainItems as $item) {
            if ($sandboxOnly && ($item->type != 'sandbox')) {
                continue;
            }
            $result[] = $item->name;
        }

        return $result;
    }

    private function extractPageHash($url)
    {
        if (empty($url)) {
            return '';
        }
        $exploded = explode('/',$url);
        return array_pop($exploded);
    }

    /**
     * @param String $url
     * @param array $query
     * @return \stdClass
     * @throws Exception
     */
    public function request($url, $query = [])
    {
        $response = $this->mailgun->get($url, $query);
        $responseCode = $response->http_response_code;
        if ($responseCode != 200) {
            throw new Exception("getDomains mailgun error: $responseCode");
        }
        return $response->http_response_body;
    }
}