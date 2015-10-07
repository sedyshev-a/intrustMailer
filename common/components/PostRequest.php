<?php
namespace common\components;

use Yii;
use yii\base\Configurable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;

class PostRequest implements Configurable
{
    private $uri;

    public function __construct($config = [])
    {
        if (!empty($config)) {
            Yii::configure($this, $config);
        }
    }



    //region Getters&Setters
    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param mixed $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }
    //endregion


}