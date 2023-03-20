<?php

namespace Balance1230\dxsendmail;

use GuzzleHttp\Client;

class Http
{
    const SEND_URI = '/mail/insideSend';

    public $url='http:127.0.0.1';


    public function __construct(string $url)
    {
        $this->url=$url;
    }

    public function ApiSend(array $params):array
    {
        return $this->http($this->url.self::SEND_URI,$params);
    }

    private function http(string $uriPatch,array $params=[]):array
    {
        var_dump((new Client())->post($uriPatch,$params)->getBody());
    }
}