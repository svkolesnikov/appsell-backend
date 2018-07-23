<?php

namespace App\Lib\Enum;

interface TitlesAwareInterface
{
    public static function getTitles(): array;

    public static function getTitleByValue(string $value): string;
}