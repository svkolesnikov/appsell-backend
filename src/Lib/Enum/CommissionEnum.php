<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static CommissionEnum SERVICE()
 * @method static CommissionEnum SELLER()
 * @method static CommissionEnum SOLAR_STAFF_PAYOUT()
 */
final class CommissionEnum extends Enum implements TitlesAwareInterface
{
    use TitlesAwareTrait;

    // Комиссия, взимаемая сервисом от оплаты заказчиком
    // 100 – комиссия сервиса
    public const SERVICE = 'service';

    // Комиссия, взимаемая продавцом от награды сотрудника
    // 100 - комиссия сервиса - комиссия продавца
    public const SELLER  = 'seller';

    // Комиссия, взимаемая сервисом SolarStaff при
    // выводе денег через него
    public const SOLAR_STAFF_PAYOUT = 'solar_staff_payout';

    protected static $titles = [
        self::SERVICE            => 'Сервис',
        self::SELLER             => 'Продавец',
        self::SOLAR_STAFF_PAYOUT => 'SolarStaff'
    ];
}