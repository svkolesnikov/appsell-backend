<?php

namespace App\SolarStaff;

use App\Exception\Api\SolarStaffException;
use Psr\Log\LoggerInterface;

class Transport
{
    /** @var string */
    protected $url;

    /** @var string */
    protected $clientId;

    /** @var string */
    protected $salt;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(string $url, string $clientId, string $salt, LoggerInterface $logger)
    {
        $this->url = $url;
        $this->clientId = $clientId;
        $this->salt = $salt;
        $this->logger = $logger;
    }

    /**
     * @param string $method
     * @param array $params
     * @return array
     * @throws SolarStaffException
     */
    public function sendRequest(string $method, array $params = []): array
    {
        $ch = curl_init();

        $apiEndpoint = $this->url . $method;
        $postFields  = $this->getSignedParams($params);

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_URL, $apiEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        $this->logger->debug('Отправка запроса в API SolarStaff', [
            'params'      => $params,
            'url'         => $apiEndpoint,
            'post_fields' => $postFields
        ]);

        $curlResponse = curl_exec($ch);
        if (false === $curlResponse) {

            $this->logger->error('Ошибка обращения к API SolarStaff: ' . curl_error($ch));
            throw new SolarStaffException(curl_error($ch));

        }

        $response = json_decode($curlResponse, true);
        if (200 !== $response['code']) {

            // При вызове метода вывода средств, может быть ситуация, когда
            // задача на стороне solar создалась, но нам вернуло ошибку
            // в этом случае мы считаем вывод средств успешным (например,
            // на балансе компании было меньше средств чем нужно для вывода,
            // и вывод на стороне solar произойдет позже)
            //
            // Поэтому залогируем эту информацию, а вызывающему коду
            // скажем, что все прошло хорошо

            $isCompletePayout = $params['action'] === 'payout'
                && isset($response['response']['task_id'])
                && is_numeric($response['response']['task_id']);

            if ($isCompletePayout) {

                $this->logger->warning(
                    'Вывод средств в SolarStaff прошел успешно. Но была получена ошибка' . $curlResponse
                );

            } else {

                // Залогируем ответ солара
                $this->logger->error('Ошибка обращения к API SolarStaff: ' . $curlResponse);

                // Выбросим исключение
                throw new SolarStaffException(
                    $response['response']['error_text'] ?? 'Неизвестная ошибка при обращении к API solar staff'
                );
            }
        }

        $this->logger->debug('Успешный ответ от API SolarStaff', (array) $response);
        return $response;
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