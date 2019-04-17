<?php
namespace App\Form;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Lib\Enum\UserGroupEnum;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
            ->add('lastname',       TextType::class,    ['required' => false, 'label' => 'Фамилия'])
            ->add('firstname',      TextType::class,    ['required' => false, 'label' => 'Имя'])
            ->add('phone',          IntegerType::class, ['required' => false, 'label' => 'Телефон'])
            ->add('company_id',     TextType::class,    ['required' => false, 'label' => 'Идентификатор компании'])
            ->add('company_title',  TextType::class,    ['required' => false, 'label' => 'Наименование компания'])
            ->add('employer',       EntityType::class,  [
                'class'             => User::class,
                'required'          => false,
                'label'             => 'Работодатель',
                'query_builder'     => function (EntityRepository $er) {
                    return $er
                        ->createQueryBuilder('u')
                        ->innerJoin('u.groups', 'g')
                        ->where('g.code = :code')
                        ->setParameter(':code', UserGroupEnum::SELLER);
                },
            ])

            ->add('company_payout_over_solar_staff', null, ['required' => false, 'label' => 'Оплачивает через solar staf'])
        ;
    }

}