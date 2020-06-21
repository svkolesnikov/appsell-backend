<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FilterClickStatsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('GET')
            ->add(
                'seller_email',
                EmailType::class,
                [
                    'label' => 'E-mail продавца',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'E-mail продавца'
                    ]
                ]
            )
            ->add(
                'date_from',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'label' => 'Дата начала',
                    'data' => (new \DateTime())->add(\DateInterval::createFromDateString('-1 month')),
                ]
            )
            ->add(
                'date_to',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'label' => 'Дата окончания',
                    'data' => new \DateTime(),
                ]
            )
            ->add(
                'offer_id',
                TextType::class,
                [
                    'label' => 'Offer Id',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Идентификатор оффера'
                    ]
                ]
            );
    }
}