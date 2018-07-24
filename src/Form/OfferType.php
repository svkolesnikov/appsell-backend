<?php

namespace App\Form;

use App\Entity\Compensation;
use App\Entity\Offer;
use App\Entity\OfferLink;
use App\Form\DataTransformer\StringToOfferTypeDataTransformer;
use App\Lib\Enum\CompensationTypeEnum;
use App\Lib\Enum\OfferTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OfferType extends AbstractType
{
    protected $roles;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type',           ChoiceType::class,      [
                'required'      => true,
                'label'         => 'Тип',
                'choices'       => array_flip(OfferTypeEnum::getTitles()),
            ])
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
                'delete_empty'  => true,
                'constraints'   => [new Callback([$this, 'validateOfferLink'])]
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
                'delete_empty'  => true,
                'constraints'   => [new Callback([$this, 'validateCompensation'])]
            ])
        ;

        $builder->get('type')->addModelTransformer(new StringToOfferTypeDataTransformer());
    }

    public function validateOfferLink($payload, ExecutionContextInterface $context)
    {
        $types = [];

        /** @var OfferLink $item */
        foreach ($payload as $item) {
            $types[] = $item->getType();
        }

        if (\count($payload) !== \count(array_unique($types))) {
            $context
                ->buildViolation('Оффер не может содержать ссылки с одинаковым типом')
                ->atPath('links')
                ->addViolation();
        }
    }

    public function validateCompensation($payload, ExecutionContextInterface $context)
    {
        $baseCompensationCount = 0;

        /** @var Compensation $item */
        foreach ($payload as $item) {

            if (CompensationTypeEnum::BASE === $item->getType()->getValue()) {
                $baseCompensationCount++;
            }
        }

        if (0 === $baseCompensationCount) {
            $context
                ->buildViolation('Не указана базовая компенсация')
                ->atPath('compensations')
                ->addViolation();
        }

        if (1 < $baseCompensationCount) {
            $context
                ->buildViolation('Может быть только одна базовая компенсация')
                ->atPath('compensations')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Offer::class
        ]);
    }
}