<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * Список заранее определенных кодов лдя действий
 * например, установка, есть у каждого действия.
 * Такие действия у офферов должны создаваться
 * автоматически и их нельзя удалить.
 *
 * @method static PredefinedActionEnum INSTALLATION()
 */
final class PredefinedActionEnum extends Enum
{
    public const INSTALLATION = 'installation';
}