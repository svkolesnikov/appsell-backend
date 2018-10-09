<?php

namespace App\SolarStaff;

class Client
{
    /** @var Transport */
    protected $transport;

    /** @var string */
    protected $ofertaUrl;

    /** @var string */
    protected $loginUrl;

    /** @var string */
    protected $employerId;

    public function __construct(Transport $transport, string $loginUrl, string $ofertaUrl, string $employerId)
    {
        $this->transport  = $transport;
        $this->ofertaUrl  = $ofertaUrl;
        $this->loginUrl   = $loginUrl;
        $this->employerId = $employerId;
    }

    public function getLoginUrl(): string
    {
        return $this->loginUrl;
    }

    public function getOfertaUrl(): string
    {
        return $this->ofertaUrl;
    }

    public function getEmployerId(): string
    {
        return $this->employerId;
    }

    /**
     * @param string $email
     * @param string $password
     * @return int Идентификатор сотрудника на стороне solar staff
     * @throws \App\Exception\Api\SolarStaffException
     */
    public function createWorker(string $email, string $password): int
    {
        $response = $this->transport->sendRequest('/v1/workers', [
            'action' => 'worker_create',
            'email' => $email,
            'password' => $password,
            'first_name' => '…',
            'last_name' => '…',
            'specialization' => 385,
            'country' => 'RU',
            'send_message' => 1
        ]);

        return $response['response']['id'];
    }

    public function payout(array $params): array
    {
//        [
//            "action" => "payout",
//            "worker_id" => "",
//            "currency" => "RUB",
//            "amount" => 3000,
//            "todo_attributes" => "http://some.domain.com",
//        ]
    }
}