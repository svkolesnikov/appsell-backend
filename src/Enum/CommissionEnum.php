<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

final class CommissionEnum extends Enum
{
    // Комиссия, взимаемая сервисом от оплаты заказчиком
    // 100 – комиссия сервиса
    public const SERVICE = 'service';

    // Комиссия, взимаемая продавцом от награды сотрудника
    // 100 - комиссия сервиса - комиссия продавца
    public const SELLER  = 'seller';
}