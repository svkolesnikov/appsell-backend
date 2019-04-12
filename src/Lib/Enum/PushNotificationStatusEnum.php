<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static PushNotificationStatusEnum NEW()
 * @method static PushNotificationStatusEnum IN_PROCESS()
 * @method static PushNotificationStatusEnum SUCCESS()
 * @method static PushNotificationStatusEnum ERROR()
 */
final class PushNotificationStatusEnum extends Enum implements TitlesAwareInterface
{
    use TitlesAwareTrait;

    public const NEW        = 'new';
    public const IN_PROCESS = 'in_process';
    public const SUCCESS    = 'success';
    public const ERROR      = 'error';

    protected static $titles = [
        self::NEW        => 'Ожидает обработки',
        self::IN_PROCESS => 'В обработке',
        self::SUCCESS    => 'Обработан',
        self::ERROR      => 'Ошибка'
    ];
}