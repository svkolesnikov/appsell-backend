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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EventsCsvImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('delimeter', ChoiceType::class,      [
                'required'    => true,
                'label'       => 'Разделитель',
                'placeholder' => 'Не выбрано',
                'choices'     => [
                    'Запятая (,)' => ',',
                    'Точка с запятой (;)' => ';'
                ],
            ])
            ->add('click_id_column_number', ChoiceType::class, [
                'required' => true,
                'label'    => '№ колонки с ClickID',
                'placeholder' => 'Не выбрано',
                'choices'     => array_combine(
                    range(1, 20),
                    range(1, 20)
                ),
            ])
            ->add('file', FileType::class, [
                'required' => true,
                'label'    => 'Файл с событиями',
            ])
        ;
    }
}