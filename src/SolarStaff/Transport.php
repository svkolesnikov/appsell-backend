<?php

namespace App\SolarStaff;

class Transport
{
    /** @var string */
    protected $url;

    /** @var string */
    protected $clientId;

    /** @var string */
    protected $salt;

    public function __construct(string $url, string $clientId, string $salt)
    {
        $this->url = $url;
        $this->clientId = $clientId;
        $this->salt = $salt;
    }

    public function sendRequest(string $method, array $params = []): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURL_SSLVERSION_SSLv2, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_URL, $this->url . '/' . $method);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getSignedParams($params));

        $curlResponse = curl_exec($ch);

        return json_decode($curlResponse, true);
    }

    protected function getSignedParams(array $params = []): array
    {
        $params['client_id'] = $this->clientId;

        ksort($params);

        $params['signature'] = sha1(
            implode(';',array_map(function ($k, $v) { return ($k . ':' . $v); }, array_keys($params), $params)) .
            ';' .
            $this->salt
        );

        return $params;
    }
}