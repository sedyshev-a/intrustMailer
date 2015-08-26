<?php
namespace common\components;

use Yii;
use yii\base\Exception;
use yii\base\ErrorException;
use yii\helpers\BaseFileHelper;
use yii\base\Object;

class Ftp extends Object
{
    public $host = '';
    public $port = 21;
    public $timeout = 90;

    public $username = '';
    public $password = '';

    public $passiveMode = true;

    public $localDir = '';

    protected $lastError = '';

    /** @var resource|bool Connection resource, or FALSE, if no connection */
    protected $connection = false;
    protected $ready = false;

    public function connect()
    {
        if ($this->connection !== false) {
            return true;
        }
        try {
            $conn = ftp_connect($this->host, $this->port, $this->timeout);
        } catch (ErrorException $e) {
            $this->connection = false;
            $this->setLastError($e->getMessage());
            return false;
        }
        $this->connection = $conn;
        return true;
    }

    public function disconnect()
    {
        if ($this->connection === false) {
            $this->ready = false;
            return true;
        }
        try {
            ftp_close($this->connection);
        } catch (ErrorException $e) {
            $this->connection = false;
            $this->ready = false;
            $this->setLastError($e->getMessage());
            return false;
        }
        $this->connection = false;
        $this->ready = false;

        return true;
    }

    public function login()
    {
        if ($this->connection === false) {
            return false;
        }
        try {
            $result = ftp_login($this->connection, $this->username, $this->password);
        } catch(ErrorException $e) {
            $this->setLastError($e->getMessage());
            $result = false;
        }
        $this->ready = $result;
        if ($result !== false) {
            ftp_pasv($this->connection, $this->passiveMode);
        }
        return $result;
    }

    public function ls($path = null)
    {
        if (!$this->isReady()) {
            return false;
        }
        try {
            $result = ftp_nlist($this->connection, $path);
        } catch (ErrorException $e) {
            $this->setLastError($e->getMessage());
            $result = false;
        }
        return $result;
    }
    public function lsDir($path = null)
    {
        if (!$this->isReady()) {
            return false;
        }
        try {
            $list = ftp_rawlist($this->connection, $path);
        } catch (ErrorException $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
        $result = [];
        foreach ($list as $item) {
            $splitted = preg_split("/\s+/", $item);
            if ($splitted[0]{0} !== 'd') {
                continue;
            }
            $result[] = $splitted[8];
        }
        return $result;
    }
    public function lsFiles($path = null, $minSize = null)
    {
        if (!$this->isReady()) {
            return false;
        }
        try {
            $list = ftp_rawlist($this->connection, $path);
        } catch (ErrorException $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
        $result = [];
        foreach ($list as $item) {
            $splitted = preg_split("/\s+/", $item);
            if ($splitted[0]{0} === 'd') {
                continue;
            }
            if (!is_null($minSize) && $splitted[4] <= $minSize) {
                continue;
            }
            $result[] = $splitted[8];
        }
        return $result;
    }
    public function lsFilesCriteria($path = null)
    {
        if (!$this->isReady()) {
            return false;
        }
        try {
            $list = ftp_rawlist($this->connection, $path);
        } catch (ErrorException $e) {
            $this->setLastError($e->getMessage());
            return false;
        }
        $result = [];
        foreach ($list as $item) {
            $splitted = preg_split("/\s+/", $item);
            if ($splitted[0]{0} === 'd' ) {
                continue;
            }
            $result[] = $splitted[8];
        }
        return $result;
    }
    public function chDir($dirName)
    {
        if (!isset($dirName) || !$this->isReady()) {
            return false;
        }
        try {
            $result = ftp_chdir($this->connection, $dirName);
        } catch (ErrorException $e) {
            $this->setLastError($e->getMessage());
            $result = false;
        }
        return $result;
    }
    public function get($filePath, $localPath = null)
    {
        if (!$this->isReady() || empty($filePath)) {
            return false;
        }
        if (is_null($localPath)) {
            if (!empty($this->localDir)) {
                $localPath = $this->localDir;
            }
            else return false;
        }

        try {
            Yii::trace($localPath . '/'.basename($filePath));
            BaseFileHelper::createDirectory($localPath);
        } catch (Exception $e) {
            $this->setLastError($e->getMessage());
            return false;
        }

        try {
            Yii::trace($localPath . '/'.basename($filePath));

            $result = ftp_get($this->connection, $localPath . '/'.basename($filePath), $filePath, FTP_BINARY);
        } catch (ErrorException $e) {
            $this->setLastError($e->getMessage());
            $result = false;
        }
        return $result;
    }


    public function setTimeout($timeout) {
        if (!is_int($timeout) || $timeout < 1 || !$this->connected()) {
            return false;
        }
        $this->timeout = $timeout;
        try {
            $result = ftp_set_option($this->connection, \FTP_TIMEOUT_SEC, $timeout);
        } catch (ErrorException $e) {
            $this->setLastError($e->getMessage());
            $result = false;
        }
        return $result;
    }



    public function getLastError()
    {
        return $this->lastError;
    }

    protected function setLastError($errorMessage)
    {
        $this->lastError = $errorMessage;
    }

    protected function connected()
    {
        return $this->connection !== false;
    }
    public function isReady()
    {
        return $this->connected() && $this->ready;
    }

}