<?php
namespace common\components\zContractParser;

use Yii;
use yii\base\Exception;

abstract class AbstractSupplier
{
    const DT_FORMAT = 'Y-m-d';

    public $type;
    public $fullName;
    public $shortName;
    public $firmName;
    public $regDate;
    public $inn;
    public $kpp;
    public $address;
    public $emails;
    public $phone;
    public $contactLastName;
    public $contactFirstName;
    public $contactMiddleName;

    protected $xml;

    public function __construct($xml)
    {
        if (!($xml instanceof \SimpleXMLElement)) {
            throw new Exception('Invalid type');
        }
        $this->xml = $xml;
    }

    public function getValidInfo()
    {
        $info = $this->getInfo();
        $validInn = $this->validateINN();
        if (!$validInn) {
            print $this->inn . PHP_EOL;
            return false;
        }
        $validMail = $this->validateEmails();
        if (!$validMail) {
            print_r($this->emails);
            return false;
        }

        return $info;
    }
    abstract public function getInfo();

    protected function validateEmails()
    {
        $validEmails = [];
        $pattern = "/^([\\p{L}\\.\\-\\_\\d]+)@([\\p{L}\\-\\.\\d]+)((\\.(\\p{L}){2,63})+)$/ui";
        $emails = $this->emails;
        foreach ($emails as $email) {
            if (empty($email) || strlen($email) < 7) {
                continue;
            }
            $result = (bool)preg_match($pattern, $email);
            if ($result === false) {
                continue;
            }
            $validEmails[] = $email;
        }

        $this->setEmails($validEmails);

        return count($validEmails) > 0;
    }
    protected function extractEmails($raw)
    {
        if ($raw instanceof \SimpleXMLElement) {
            $raw = (string)$raw;
        }
        $rawString = str_replace(['/', ','],'.',$raw);
        // забирает e-mail из текста, работает и с кириллическими доменами в т.ч.
        $pattern ="/([\\p{L}\\.\\-\\_\\d]+)@([\\p{L}\\-\\.\\d]+)((\\.(\\p{L}){1,63})+)/iu";
        preg_match_all($pattern, $rawString, $matches);
        if (!isset($matches[0]) || empty($matches[0])) {
            return [];
        }
        $emails = $matches[0];
        $countEmails = count($emails);
        if ($countEmails > 1) {
            $emails = array_keys(array_flip($emails)); // оставит только уникальные адреса
        }
        foreach ($emails as $key => $value) {
            $emails[$key] = mb_strtolower($value);
        }
        return $emails;
    }
    protected function filterEmails($emails)
    {
        $tldReplacements = [
            'кг' => 'ru',
            'r' => 'ru',
            'u' => 'ru'
        ];
        $stopUsernames = Yii::$app->params['stopList']['usernames'];
        $stopDomains =  Yii::$app->params['stopList']['domains'];
        $result = [];
        foreach ($emails as $email) {
            $exploded = explode('@',$email);
            if (empty($exploded) || count($exploded) > 2) {
                continue;
            }
            $user = $exploded[0];
            if (in_array($user, $stopUsernames)) {
                continue;
            }
            $domain = $exploded[1];
            if (in_array($domain, $stopDomains)) {
                continue;
            }
            $exploded = explode('.',$domain);
            if (count($exploded) < 2) {
                continue;
            }
            $tldIndex = count($exploded) - 1;
            $tld = $exploded[$tldIndex];

            foreach ($tldReplacements as $from => $to) {
                if ($tld === $from) {
                    $exploded[$tldIndex] = $to;
                    break;
                }
            }
            $domain = implode('.',$exploded);
            $mail = "{$user}@{$domain}";
            $isCyrillic = ($tld === 'рф');
            if ($isCyrillic) {
                $result[] = $mail;
                continue;
            }
            $cyrLettersCount = $this->countCyrillicLetters($mail);
            if ($cyrLettersCount < 1) {
                $result[] = $mail;
                continue;
            }
            $mail = $this->rusToLat($mail);
            $result[] = $mail;
        }

        return $result;
    }
    private function countCyrillicLetters($str)
    {
        $pattern = "/[а-яА-Я]/u";
        preg_match_all($pattern, $str, $matches);
        if (!isset($matches[0])) {
            return 0;
        }

        return count($matches[0]);
    }
    private function rusToLat($str)
    {
        $replacements = [
            'а' => 'a', 'е' => 'e', 'к' => 'k', 'о' => 'o',
            'р' => 'p', 'с' => 'c', 'у' => 'y', 'х' => 'x',
            'в' => 'b', 'м' => 'm', 'н' => 'h', 'т' => 't',
        ];
        $str = strtr($str,$replacements);

        return $str;
    }
    protected function validateINN()
    {
        $inn = $this->inn;
        if (preg_match('/\D/', $inn)) {
            return false;
        }

        $inn = (string)$inn;
        $len = strlen($inn);

        if ($len === 10) {
            return $inn[9] === (string) (((
                        2*$inn[0] + 4*$inn[1] + 10*$inn[2] +
                        3*$inn[3] + 5*$inn[4] +  9*$inn[5] +
                        4*$inn[6] + 6*$inn[7] +  8*$inn[8]
                    ) % 11) % 10);
        }
        if ( $len === 12 )
        {
            $num10 = (string) (((
                        7*$inn[0]  + 2*$inn[1] + 4*$inn[2] +
                        10*$inn[3] + 3*$inn[4] + 5*$inn[5] +
                        9*$inn[6]  + 4*$inn[7] + 6*$inn[8] +
                        8*$inn[9]
                    ) % 11) % 10);

            $num11 = (string) (((
                        3*$inn[0] +  7*$inn[1] + 2*$inn[2] +
                        4*$inn[3] + 10*$inn[4] + 3*$inn[5] +
                        5*$inn[6] +  9*$inn[7] + 4*$inn[8] +
                        6*$inn[9] +  8*$inn[10]
                    ) % 11) % 10);

            return $inn[11] === $num11 && $inn[10] === $num10;
        }

        return false;
    }
    protected function toArray()
    {
        $result = [];
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $name = $property->getName();
            $result[$name] = $this->{$name};
        }

        return $result;
    }
    protected function formatDate($strDate)
    {
        $date = \DateTime::createFromFormat(self::DT_FORMAT, $strDate);
        if ($date === false) {
            return false;
        }
        $date->setTime(0,0,0);

        return $date;
    }

    // ================================================================
    // ==================== GETTERS & SETTERS =========================
    // ================================================================

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        if (!is_string($type)) {
            $type = (string)$type;
        }
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @param mixed $fullName
     */
    public function setFullName($fullName)
    {
        if (!is_string($fullName)) {
            $fullName = (string)$fullName;
        }
        $this->fullName = trim($fullName);
    }

    /**
     * @return mixed
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * @param mixed $shortName
     */
    public function setShortName($shortName)
    {
        if (!is_string($shortName)) {
            $shortName = (string)$shortName;
        }
        $this->shortName = trim($shortName);
    }

    /**
     * @return mixed
     */
    public function getFirmName()
    {
        return $this->firmName;
    }

    /**
     * @param mixed $firmName
     */
    public function setFirmName($firmName)
    {
        if (!is_string($firmName)) {
            $firmName = (string)$firmName;
        }
        $this->firmName = trim($firmName);
    }

    /**
     * @return \DateTime
     */
    public function getRegDate()
    {
        return $this->regDate;
    }

    /**
     * @param string|\DateTime $regDate
     */
    public function setRegDate($regDate)
    {
        if ($regDate instanceof \DateTime) {
            $this->regDate = $regDate;
            return;
        }
        if (!is_string($regDate)) {
            if ($regDate instanceof \SimpleXMLElement) {
                $regDate = (string)$regDate;
            }
            else return;
        }
        $date = $this->formatDate($regDate);
        if ($date !== false) {
            $this->regDate = $regDate;
        }
    }

    /**
     * @return string
     */
    public function getInn()
    {

        return $this->inn;
    }

    /**
     * @param mixed $inn
     */
    public function setInn($inn)
    {
        if (!is_string($inn)) {
            $inn = (string)$inn;
        }
        $this->inn = trim($inn);
    }

    /**
     * @return string
     */
    public function getKpp()
    {
        return $this->kpp;
    }

    /**
     * @param mixed $kpp
     */
    public function setKpp($kpp)
    {
        if (!is_string($kpp)) {
            $kpp = (string)$kpp;
        }
        $this->kpp = trim($kpp);
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        if (!is_string($address)) {
            $address = (string)$address;
        }
        $this->address = trim($address);
    }

    /**
     * @return mixed
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * @param string|array $emails
     */
    public function setEmails($emails)
    {
        if (is_string($emails)) {
            $emails = [trim($emails)];
        }
        $this->emails = $emails;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone)
    {
        if (!is_string($phone)) {
            $phone = (string)$phone;
        }
        $this->phone = trim($phone);
    }

    /**
     * @return mixed
     */
    public function getContactLastName()
    {
        return $this->contactLastName;
    }

    /**
     * @param mixed $contactLastName
     */
    public function setContactLastName($contactLastName)
    {
        if (!is_string($contactLastName)) {
            $contactLastName = (string)$contactLastName;
        }
        $this->contactLastName = $contactLastName;
    }

    /**
     * @return mixed
     */
    public function getContactFirstName()
    {
        return $this->contactFirstName;
    }

    /**
     * @param mixed $contactFirstName
     */
    public function setContactFirstName($contactFirstName)
    {
        if (!is_string($contactFirstName)) {
            $contactFirstName = (string)$contactFirstName;
        }
        $this->contactFirstName = $contactFirstName;
    }

    /**
     * @return mixed
     */
    public function getContactMiddleName()
    {
        return $this->contactMiddleName;
    }

    /**
     * @param mixed $contactMiddleName
     */
    public function setContactMiddleName($contactMiddleName)
    {
        if (!is_string($contactMiddleName)) {
            $contactMiddleName = (string)$contactMiddleName;
        }
        $this->contactMiddleName = $contactMiddleName;
    }
}