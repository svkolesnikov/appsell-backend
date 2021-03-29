<?php

namespace App\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class TextArray extends \MartinGeorgiev\Doctrine\DBAL\Types\TextArray
{
    /**
     * @return array<string>
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return [self::TYPE_NAME];
    }
}