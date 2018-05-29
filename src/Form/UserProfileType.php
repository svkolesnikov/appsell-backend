<?php
namespace App\Form;

use App\Entity\UserProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserProfileType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => UserProfile::class,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lastname',   TextType::class,    ['required' => false, 'label' => 'Фамилия'])
            ->add('firstname',  TextType::class,    ['required' => false, 'label' => 'Имя'])
            ->add('phone',      IntegerType::class, ['required' => false, 'label' => 'Телефон'])
        ;
    }
}