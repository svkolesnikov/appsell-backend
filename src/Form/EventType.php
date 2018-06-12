<?php

namespace App\Form;

use App\Entity\EventType as EventTypeEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    protected $roles;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code',   TextType::class,    ['required' => true, 'label' => 'Код'])
            ->add('title',  TextType::class,    ['required' => true, 'label' => 'Наименование'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EventTypeEntity::class
        ]);
    }
}