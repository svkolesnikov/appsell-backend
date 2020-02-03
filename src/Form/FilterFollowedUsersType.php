<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FilterFollowedUsersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('GET')
            ->add('email', TextType::class,      [
                'required'    => true,
                'label'       => 'E-mail',
                'attr' => [
                    'placeholder' => 'Введите email пользователя'
                ]
            ])
        ;
    }
}