<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

abstract class BaseEnum extends Enum
{
    /**
     * Хеш с названиями элементов перечисления на русском языке
     * Не обязателен для перегрузки
     * @var array
     */
    protected static $titles = [];

    /**
     * Возвращает хэш вида key => value,
     * где key - значение константы в перечислении,
     * а value - русское название константы в перечислении
     * @return array
     */
    public static function getTitles()
    {
        return static::$titles;
    }
}