<?php

namespace App\Form;

use App\Entity\OfferApp;
use App\Enum\StoreEnum;
use App\Form\DataTransformer\StringToStoreEnumDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferAppType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url',        TextType::class,    ['required' => true, 'label' => false])
            ->add('store',      ChoiceType::class,  [
                'required'      => true,
                'label'         => false,
                'label_attr'    => ['style' => 'font-size:14px;font-weight:700'],
                'choices'       => StoreEnum::toArray()
            ])
        ;

        $builder->get('store')->addModelTransformer(new StringToStoreEnumDataTransformer());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => OfferApp::class,
        ));
    }
}