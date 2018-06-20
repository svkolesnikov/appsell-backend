<?php

namespace App\Form\DataTransformer;

use App\Lib\Enum\CompensationTypeEnum;
use Symfony\Component\Form\DataTransformerInterface;

class StringToCompensationTypeDataTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return $value instanceof CompensationTypeEnum ? $value->getValue() : $value;
    }

    /**
     * @param mixed $value
     * @return CompensationTypeEnum|mixed
     * @throws \UnexpectedValueException
     */
    public function reverseTransform($value)
    {
        return new CompensationTypeEnum($value);
    }
}