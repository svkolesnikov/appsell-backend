<?php

namespace App\Admin;

use App\Enum\CurrencyEnum;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\CoreBundle\Form\Type\CollectionType;

class OfferAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'offers';

    public function getBatchActions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('Общая информация')
                ->add('title', null, ['required' => true])
                ->add('description', null)
                ->add('price', null)
                ->add('currency', ChoiceType::class, [
                    'choices' => CurrencyEnum::toArray()
                ])

//                ->add('active_from', DateType::class, [
//                    'label' => 'Дата завершения действия',
//                    'widget' => 'single_text',
//                    'attr' => ['style' => 'width:150px'],
//                    'required' => false
//                ])
//                ->add('active_to', DateType::class, [
//                    'label' => 'Дата завершения действия',
//                    'widget' => 'single_text',
//                    'attr' => ['style' => 'width:150px'],
//                    'required' => false
//                ])

                ->add('apps', CollectionType::class, [
//                        'cascade_validation' => true,
                        'by_reference' => false
                    ],
                    [
                        'edit' => 'inline',
                        'inline' => 'table'
                    ]
                )
//                ->add('apps', ModelType::class, [
//                    'by_reference' => true,
//                    'btn_add' => true,
//                    'btn_delete' => true,
//                    'btn_list' => 'Выбрать',
//                    'property' => 'store'
//                ])

//            ->end()
//            ->with('Группы')
//                ->add('groups', ModelType::class, [
//                    'required' => false,
//                    'expanded' => true,
//                    'multiple' => true,
//                    'label' => 'Группы'
//                ])
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filterMapper)
    {
        $filterMapper
            ->add('email')
            ->add('groups')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', null)
            ->add('owner.email', null)
            ->add('title', null)
            ->add('description', null)
            ->add('active_from', null)
            ->add('active_to', null)
            ->add('_action', null, [
                'label' => 'Действия',
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ]
            ])
        ;
    }

    protected function configureShowFields(ShowMapper $show)
    {
        $show
            ->with('Общая информация', [
                //'class'       => 'col-md-8',
                'box_class'   => 'box box-solid box-info'
            ])
                ->add('id', null)
                ->add('owner.email', null)
                ->add('title', null)
                ->add('description', null)
                ->add('active_from', null)
                ->add('active_to', null)
            ->end()
            ->with('Доступные действия', [
                'box_class'   => 'box box-solid box-info'
            ])
                ->add('actions', CollectionType::class, [
                    'associated_property' => 'title'
                ])
            ->end()
            ->with('Приложения', [
                'box_class'   => 'box box-solid box-info'
            ])
                ->add('apps', CollectionType::class, [
                    'associated_property' => 'url'
                ])
            ->end();
    }
}