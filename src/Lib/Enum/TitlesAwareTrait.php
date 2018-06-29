<?php

namespace App\Lib\Enum;

trait TitlesAwareTrait
{
    protected static $titles = [];

    public static function getTitles(): array
    {
        return static::$titles;
    }

    public static function getTitleByValue(string $value): string
    {
        return static::$titles[$value];
    }
}