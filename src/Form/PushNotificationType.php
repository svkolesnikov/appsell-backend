<?php

namespace App\Form;

use App\Entity\Offer;
use App\Entity\User;
use App\Lib\Enum\UserGroupEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PushNotificationType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'user' => null,
            'is_seller' => false,
            'is_admin' => false
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('offer_id',       HiddenType::class)
            ->add('message',        TextareaType::class, [
                'required'          => true,
                'label'             => 'Текстовое сообщение',
                'attr'              => ['style' => 'height: 100px;']
            ]);

        if ($options['is_admin']) {
            $builder->add('offer', EntityType::class, [
                'required'          => false,
                'class'             => Offer::class,
                'choice_label' => function(Offer $offer, $key, $value) {
                    return $offer->getTitle();
                },
                'label'             => 'Оффер (не обязательно)',
                'empty_data'        => null,
                'placeholder'       => 'Выберите оффер',
                'query_builder'     => function (EntityRepository $er) use ($options) {
                    return $er
                        ->createQueryBuilder('o')
                        ->where(':date between o.active_from AND o.active_to ')
                        ->setParameter('date', date('Y-m-d H:i:s'));
                }
            ]);
        }

        if ($options['is_seller']) {
            $builder->add('users',  ChoiceType::class, [
                'error_bubbling'    => true,
                'multiple'          => true,
                'expanded'          => true,
                'label'             => 'Получатели',
                'choices'           => $this->fillSellerEmployees($options['user']),
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

    private function fillSellerEmployees($user)
    {
        $users = $this->entityManager
            ->createQueryBuilder()
            ->select('u.id, u.email as name')
            ->from(User::class, 'u')
            ->innerJoin('u.profile', 'p')
            ->where('u.is_active = true AND p.employer = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_map(function($user) {
            return [$user['name'] => $user['id']];
        }, $users);
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