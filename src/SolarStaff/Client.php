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
            'specialization' => 8,
            'country' => 'RU',
            'send_message' => 1
        ]);

        return $response['response']['id'];
    }

    /**
     * Вывод денег на внутренний счет в Solar Staff
     *
     * @param int $workerId Идентификатор в solar staff
     * @param int $amount Сумма
     * @param array $attributes todo_attributes
     * @return array Информация о транзакции
     * @throws \App\Exception\Api\SolarStaffException
     */
    public function payout(int $workerId, int $amount, array $attributes): array
    {
        $response = $this->transport->sendRequest('/v1/payment', [
            'action'          => 'payout',
            'worker_id'       => $workerId,
            'currency'        => 'RUB',
            'amount'          => $amount,
            'todo_type'       => 27,
            'todo_attributes' => implode(';', $attributes),
        ]);

        return $response['response'];
    }
}