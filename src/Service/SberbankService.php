<?php

namespace App\Service;

use App\Entity\Payments;
use App\Entity\RequestLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SberbankService
{
    private string $clientId;
    private string $authorization;

    private array $scope =
        [
            'create' => 'https://api.sberbank.ru/order.create',
            'status' => 'https://api.sberbank.ru/order.status',
            'revoke' => 'https://api.sberbank.ru/order.revoke'
        ];

    private array $rquidName =
        [
            'action'   => 'x-Introspect-RqUID: ',
            'getToken' => 'rquid: '
        ];

    private array $appHeader =
        [
            'action'   => 'json',
            'getToken' => 'x-www-form-urlencoded'
        ];

    private array $url =
        [
            'create'   => 'https://dev.api.sberbank.ru/ru/prod/order/v1/creation',
            'status'   => 'https://dev.api.sberbank.ru/ru/prod/order/v1/status',
            'revoke'   => 'https://dev.api.sberbank.ru/ru/prod/order/v1/revocation',
            'getToken' => 'https://dev.api.sberbank.ru/ru/prod/tokens/v2/oauth'
        ];

    private EntityManagerInterface $em;
    private RquidService $rquid;
    private RequestLogService $log;

    public function __construct (EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->em    = $entityManager;
        $this->rquid = new RquidService($this->em);
        $this->log   = new RequestLogService($this->em);

        $this->clientId = $parameterBag->get('SBER_CLIENT_ID');
        $this->authorization = $parameterBag->get('SBER_AUTHORIZATION');
    }


    public function createPayment(array $order): array
    {
        $createTokenResponse = $this->getToken($this->scope['create'], $order['rq_uid']);

        if (! is_string($createTokenResponse )) return $createTokenResponse;

	$createToken = $createTokenResponse;

        $order = $this->getNewRquid($order);

        $header = $this->getHeader(
            "Bearer " . $createToken,
            $this->rquidName['action'] . $order['rq_uid'],
            $this->appHeader['action']
        );

        $params = $this->getParamsForCreate($order, $order['rq_uid']);

        $response = $this->sendRequest($this->url['create'], $params, $header);

        $this->log->add($order['rq_uid'], 'Создание платежа', $header, $params, $response[1]);
	
	return $response;
    }

    public function statusPayment(Payments $payment): array
    {
        $statusTokenResponse = $this->getToken($this->scope['status'], $this->rquid->get());

	if (! is_string($statusTokenResponse)) return $statusTokenResponse;

	$statusToken = $statusTokenResponse;

        $rquid = $this->rquid->get();

        $header = $this->getHeader(
            "Bearer " . $statusToken,
            $this->rquidName['action'] . $rquid,
            $this->appHeader['action']
        );

        $params = $this->getParamsForCheckAndRevoke(
            $rquid,
            $payment->getRqtm()->format('Y-m-d\TH:i:s') . 'Z',
            $payment->getOrderId()
        );

        $response = $this->sendRequest($this->url['status'], $params, $header);

        $this->log->add($rquid, 'Проверка платежа ' . $payment->getOrderId(), $header, $params, $response[1]);

        return $response;
    }

    public function revokePayment(Payments $payment): array
    {
        $revokeTokenResponse = $this->getToken($this->scope['revoke'], $this->rquid->get());
	
	if (! is_string($revokeTokenResponse)) return $revokeTokenResponse;

	$revokeToken = $revokeTokenResponse;

        $rquid = $this->rquid->get();

        $header = $this->getHeader(
            "Bearer " . $revokeToken,
            $this->rquidName['action'] . $rquid,
            $this->appHeader['action']
        );

        $params = $this->getParamsForCheckAndRevoke(
            $rquid,
            $payment->getRqtm()->format('Y-m-d\TH:i:s') . 'Z',
            $payment->getOrderId()
        );

        $response = $this->sendRequest($this->url['revoke'], $params, $header);

        $this->log->add($rquid, 'Отмена платежа ' . $payment->getOrderId(), $header, $params, $response[1]);

        return $response;
    }

    private function getParamsForCheckAndRevoke (string $rq_uid, string $rq_tm, string $order_id): string
    {
        return '{' .
            '"rq_uid": "' . $rq_uid . '",' .
            '"rq_tm": "' . $rq_tm . '",' .
            '"order_id": "' . $order_id . '"' .
            '}';
    }


    private function getParamsForCreate (array $order, string $rquid): string
    {
        return '{' .
            '"rq_uid": "' . $rquid . '",' .
            '"rq_tm": "' . $order['rq_tm'] . '",' .
            '"member_id": "' . $order['member_id'] . '",' .
            '"order_number": "' . $order['order_number'] . '",' .
            '"order_create_date": "' . $order['order_create_date'] . '",' .
            '"order_params_type": [{' .
                '"position_name": "' . $order['position_name'] . '",' .
                '"position_count": ' . $order['position_count'] . ',' .
                '"position_sum": ' . $order['position_sum'] . ',' .
                '"position_description": "' . $order['position_description'] . '"}],' .
            '"id_qr": "' . $order['id_qr'] . '",' .
            '"order_sum": ' . $order['order_sum'] . ',' .
            '"currency": "' . $order['currency'] . '",' .
            '"description": "' . $order['order_description'] . '"}'
            ;
    }

    private function getHeader (string $authToken, string $rquid, string $appHeader): array
    {
        return [
            "authorization: " . $authToken,
            $rquid,
            "X-IBM-Client-Id: " . $this->clientId,
            "accept: application/json",
            "content-type: application/" . $appHeader
        ];
    }

    private function getToken(string $scope, string $rquid)
    {

        $header = $this->getHeader(
            $this->authorization,
            $this->rquidName['getToken'] . $rquid,
            $this->appHeader['getToken']
        );

        $params = "grant_type=client_credentials&scope=" . $scope;

        $response = $this->sendRequest(
            $this->url['getToken'],
            $params,
            $header
        );

        $this->log->add($rquid, 'Получение токена для "' . $scope . '"', $header, $params, $response[1]);

        return $response[1]->access_token ?? $response;
    }

    private function sendRequest(string $url, string $params, array $header): array
    {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        $response  = json_decode(curl_exec($curl));
	$http_code = curl_getinfo($curl)['http_code'];
	
        curl_close($curl);
	

        return [$http_code, $response];
    }

    private function getNewRquid (array $order): array
    {
        $rquid = $this->rquid->get();

        if (! isset($order['rq_uid'])) {
            $order = array_merge($order, ['rq_uid' => $rquid]);
        } else {
            $order['rq_uid'] = $rquid;
        }

        return $order;
    }
}
