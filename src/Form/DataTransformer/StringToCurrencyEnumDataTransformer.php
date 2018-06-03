<?php

namespace App\Form\DataTransformer;

use App\Enum\CurrencyEnum;
use Symfony\Component\Form\DataTransformerInterface;

class StringToCurrencyEnumDataTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return $value instanceof CurrencyEnum ? $value->getValue() : $value;
    }

    /**
     * @param mixed $value
     * @return CurrencyEnum|mixed
     * @throws \UnexpectedValueException
     */
    public function reverseTransform($value)
    {
        return new CurrencyEnum($value);
    }
}