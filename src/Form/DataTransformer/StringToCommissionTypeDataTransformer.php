<?php

namespace App\Form\DataTransformer;

use App\Lib\Enum\CommissionEnum;
use Symfony\Component\Form\DataTransformerInterface;

class StringToCommissionTypeDataTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return $value instanceof CommissionEnum ? $value->getValue() : $value;
    }

    /**
     * @param mixed $value
     * @return CommissionEnum|mixed
     * @throws \UnexpectedValueException
     */
    public function reverseTransform($value)
    {
        return new CommissionEnum($value);
    }
}