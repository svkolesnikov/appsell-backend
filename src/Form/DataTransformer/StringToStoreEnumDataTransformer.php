<?php

namespace App\Form\DataTransformer;

use App\Enum\StoreEnum;
use Symfony\Component\Form\DataTransformerInterface;

class StringToStoreEnumDataTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return $value instanceof StoreEnum ? $value->getValue() : $value;
    }

    /**
     * @param mixed $value
     * @return StoreEnum|mixed
     * @throws \UnexpectedValueException
     */
    public function reverseTransform($value)
    {
        return new StoreEnum($value);
    }
}