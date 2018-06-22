<?php
namespace App\Form;

use App\Form\DataTransformer\StringToCommissionTypeDataTransformer;
use App\Lib\Enum\CommissionEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CommissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type',           ChoiceType::class, [
                'required'          => true,
                'label'             =>'Описание',
                'choices'           => array_flip(CommissionEnum::getTitles())
            ])
            ->add('description',    TextType::class, ['required' => true, 'label' => 'Описание'])
            ->add('percent',        TextType::class, ['required' => true, 'label' => 'Процент'])
        ;

        $builder->get('type')->addModelTransformer(new StringToCommissionTypeDataTransformer());
    }
}