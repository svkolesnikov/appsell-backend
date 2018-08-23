<?php

namespace App\Form;

use App\Entity\User;
use App\Lib\Enum\UserGroupEnum;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PushNotificationType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'user' => null,
            'is_seller' => false
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('offer_id',      HiddenType::class)
//            ->add('offer_id',      TextType::class)
            ->add('message',       TextType::class,     ['required' => true, 'label' => 'Текстовое сообщение']);

        if ($options['is_seller']) {
            $builder->add('users',        EntityType::class,    [
                'error_bubbling'    => true,
                'class'             => User::class,
                'multiple'          => true,
                'expanded'          => true,
                'label'             => 'Получатели',
                'query_builder'     => function (EntityRepository $er) use ($options) {
                    return $er
                        ->createQueryBuilder('u')
                        ->innerJoin('u.profile', 'p')
                        ->where('p.employer = :user')
                        ->setParameter('user', $options['user']);
                },
                'constraints'       => [new Callback([$this, 'validateUserCount'])]
            ]);

        } else {
            $builder->add('groups', ChoiceType::class, [
                'error_bubbling'    => true,
                'multiple'          => true,
                'expanded'          => true,
                'label'             => 'Группы получателей',
                'choices'           => array_flip([
                    UserGroupEnum::OWNER    => 'Заказчик',
                    UserGroupEnum::SELLER   => 'Продавец',
                    UserGroupEnum::EMPLOYEE => 'Сотрудники продавцов'
                ]),
                'constraints'       => [new Callback([$this, 'validateGroupCount'])]
            ]);
        }
    }

    public function validateGroupCount($payload, ExecutionContextInterface $context)
    {
        if (0 === \count($payload)) {
            $context
                ->buildViolation('Необходимо выбрать получателя')
                ->addViolation();
        }
    }

    public function validateUserCount($payload, ExecutionContextInterface $context)
    {
        if (0 === \count($payload)) {
            $context
                ->buildViolation('Необходимо выбрать группу получателей')
                ->addViolation();
        }
    }
}