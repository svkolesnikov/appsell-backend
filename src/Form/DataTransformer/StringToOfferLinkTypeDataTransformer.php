<?php

namespace App\Form\DataTransformer;

use App\Enum\OfferLinkTypeEnum;
use Symfony\Component\Form\DataTransformerInterface;

class StringToOfferLinkTypeDataTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return $value instanceof OfferLinkTypeEnum ? $value->getValue() : $value;
    }

    /**
     * @param mixed $value
     * @return OfferLinkTypeEnum|mixed
     * @throws \UnexpectedValueException
     */
    public function reverseTransform($value)
    {
        return new OfferLinkTypeEnum($value);
    }
}