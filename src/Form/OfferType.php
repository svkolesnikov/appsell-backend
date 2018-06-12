<?php

namespace App\Form;

use App\Entity\Offer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferType extends AbstractType
{
    protected $roles;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title',          TextType::class,        ['required' => true, 'label' => 'Наименование'])
            ->add('description',    TextareaType::class,    ['required' => false, 'label' => 'Описание'])
            ->add('active_from',    DateType::class,        [
                'required'      => true,
                'label'         => 'Дата начала',
                'widget'        => 'single_text',
                'attr'          => ['style' => 'width:135px', 'class' => 'datepick-input'],
                'html5'         => false
            ])
            ->add('active_to',      DateType::class,        [
                'required'      => true,
                'label'         => 'Дата завершения',
                'widget'        => 'single_text',
                'attr'          => ['style' => 'width:135px', 'class' => 'datepick-input'],
                'html5'         => false
            ])
            ->add('links',          CollectionType::class,  [
                'label'         => false,
                'entry_type'    => OfferLinkType::class,
                'entry_options' => ['label' => false],
                'allow_add'     => true,
                'allow_delete'  => true,
                'prototype'     => true,
                'required'      => false,
                'by_reference'  => false,
                'delete_empty'  => true
            ])

            ->add('compensations',  CollectionType::class,  [
                'label'         => false,
                'entry_type'    => OfferCompensationType::class,
                'entry_options' => ['label' => false],
                'allow_add'     => true,
                'allow_delete'  => true,
                'prototype'     => true,
                'required'      => false,
                'by_reference'  => false,
                'delete_empty'  => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Offer::class
        ]);
    }
}