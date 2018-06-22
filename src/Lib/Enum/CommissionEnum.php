<?php

namespace App\Lib\Enum;

final class CommissionEnum extends BaseEnum
{
    // Комиссия, взимаемая сервисом от оплаты заказчиком
    // 100 – комиссия сервиса
    public const SERVICE = 'service';

    // Комиссия, взимаемая продавцом от награды сотрудника
    // 100 - комиссия сервиса - комиссия продавца
    public const SELLER  = 'seller';

    protected static $titles = [
        self::SERVICE   => 'Сервис',
        self::SELLER    => 'Продавец'
    ];
}