<?php

namespace App\Form;

use App\Entity\Compensation;
use App\Enum\CompensationTypeEnum;
use App\Enum\CurrencyEnum;
use App\Form\DataTransformer\StringToCompensationTypeDataTransformer;
use App\Form\DataTransformer\StringToCurrencyDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferCompensationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type',           ChoiceType::class,      [
                'required'      => true,
                'choices'       => CompensationTypeEnum::toArray()
            ])
            ->add('description',    TextType::class,        ['required' => false, 'label' => false])
            ->add('event_type',     null,                   [
                'required'      => true,
                'attr'          => ['style'=>'width:100%']
            ])
            ->add('price',          TextType::class,        [
                'required'      => true
            ])
            ->add('currency',       ChoiceType::class,      [
                'required'      => true,
                'choices'       => CurrencyEnum::toArray()
            ])
        ;

        $builder->get('type')->addModelTransformer(new StringToCompensationTypeDataTransformer());
        $builder->get('currency')->addModelTransformer(new StringToCurrencyDataTransformer());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Compensation::class,
        ));
    }
}