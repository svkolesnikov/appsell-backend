<?php

namespace App\Form;

use App\Entity\Compensation;
use App\Entity\Offer;
use App\Entity\OfferLink;
use App\Entity\PromoCode;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Form\DataTransformer\StringToOfferTypeDataTransformer;
use App\Lib\Enum\CommissionEnum;
use App\Lib\Enum\CompensationTypeEnum;
use App\Lib\Enum\OfferTypeEnum;
use App\Lib\Enum\UserGroupEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PromoCodeType extends AbstractType
{
    protected $roles;

    /** @var UserRepository */
    private $userRepository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->userRepository = $em->getRepository(User::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        $users = $this->userRepository->findSelers();

        $builder
            ->add('offer', EntityType::class, [
                'required'          => true,
                'class'             => Offer::class,
                'choice_label' => function(Offer $offer, $key, $value) {
                    return $offer->getTitle();
                },
                'label'             => 'Оффер',
                'empty_data'        => null,
                'placeholder'       => 'Выберите оффер',
                'query_builder'     => function (EntityRepository $er) use ($options) {
                    return $er
                        ->createQueryBuilder('o');
                }
            ])
            ->add('user', EntityType::class, [
                'required'          => false,
                'class'             => User::class,
                'choice_label' => function(User $user, $key, $value) {
                    return $user->getEmail();
                },
                'label'             => 'Пользователь (не обязательно)',
                'empty_data'        => null,
                'placeholder'       => 'Выберите пользователя',
                'query_builder'     => function (EntityRepository $er) use ($options) {
                    return $er
                        ->createQueryBuilder('q')
                        ->innerJoin('q.groups', 'g')
                        ->where('g.code = :code')
                        ->setParameter(':code', UserGroupEnum::SELLER);
                }
            ])
            ->add('promoCode',          TextType::class,    ['required' => true, 'label' => 'Промо-код'])
            ->add('status',           ChoiceType::class, [
                'required'          => true,
                'label'             =>'Статус',
                'choices'           => PromoCode::getStatusesWithValues(),
            ])
            ->add('status',           ChoiceType::class, [
                'required'          => true,
                'label'             =>'Статус',
                'choices'           => PromoCode::getStatusesWithValues(),
            ])
            ->add('description',    TextareaType::class,    ['required' => false, 'label' => 'Описание'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PromoCode::class
        ]);
    }
}