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

    public function __construct(Transport $transport, string $loginUrl, string $ofertaUrl)
    {
        $this->transport = $transport;
        $this->ofertaUrl = $ofertaUrl;
    }

    public function getLoginUrl(): string
    {
        return $this->loginUrl;
    }

    public function getOfertaUrl(): string
    {
        return $this->ofertaUrl;
    }

    /**
     * @param string $email
     * @param string $password
     * @return int Идентификатор сотрудника на стороне solar staff
     */
    public function createWorker(string $email, string $password): int
    {
//        [
//            "action" => "worker_create",
//            "email" => "",
//            "password" => "",
//            "first_name" => "",
//            "last_name" => "",
//            "specialization" => 385,
//            "country" => "RU",
//            "send_message" => 1
//        ]
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