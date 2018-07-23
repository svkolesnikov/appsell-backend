<?php

namespace App\Form\DataTransformer;

use App\Lib\Enum\OfferTypeEnum;
use Symfony\Component\Form\DataTransformerInterface;

class StringToOfferTypeDataTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return $value instanceof OfferTypeEnum ? $value->getValue() : $value;
    }

    /**
     * @param mixed $value
     * @return OfferTypeEnum|mixed
     * @throws \UnexpectedValueException
     */
    public function reverseTransform($value)
    {
        return new OfferTypeEnum($value);
    }
}