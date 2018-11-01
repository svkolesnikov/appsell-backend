<?php

namespace App\Form;

use App\Entity\OfferLink;
use App\Lib\Enum\OfferLinkTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferLinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url',        TextType::class,    ['required' => true, 'label' => false])
//            ->add('type',       ChoiceType::class,  [
//                'required'      => true,
//                'label'         => false,
//                'label_attr'    => ['style' => 'font-size:14px;font-weight:700'],
//                'choices'       => OfferLinkTypeEnum::toArray(),
//                'disabled'      => true
//            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => OfferLink::class,
        ));
    }
}