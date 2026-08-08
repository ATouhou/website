<?php

namespace app\components\search\engines;

use Yii;
use yii\base\BaseObject;

class ElasticConnection extends BaseObject
{
    public $host;
    public $port;
    public $username;
    public $password;

    public function init() {
        parent::init();
    }

    public function sendRequest($url)
    {
        return file_get_contents("{$this->host}:{$this->port}{$url}", false, $this->createContext());
    }

    private function createContext()
    {
        return stream_context_create(array(
            'http' => array(
                'header' => implode("\r\n", $this->getHeaders()),
            ),
        ));
    }

    private function getHeaders()
    {
        $headers = array(
            'Authorization: Basic ' . base64_encode($this->username.':'.$this->password),
        );

        $clientIp = $this->getClientIp();
        if (!empty($clientIp)) {
            $headers[] = 'X-Forwarded-For: ' . $clientIp;
            $headers[] = 'X-Real-IP: ' . $clientIp;
        }

        $userAgent = Yii::$app->getRequest()->getUserAgent();
        if (!empty($userAgent)) {
            $headers[] = 'User-Agent: ' . $userAgent;
        }

        return $headers;
    }

    private function getClientIp()
    {
        $request = Yii::$app->getRequest();

        return $request->getHeaders()->get('CF-Connecting-IP')
            ?: $request->getHeaders()->get('X-Forwarded-For')
            ?: $request->getUserIP();
    }
}
