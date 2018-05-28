<?php

namespace App\Admin;

use App\Enum\CurrencyEnum;
use App\Enum\StoreEnum;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class OfferAppAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'offer-apps';

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
            ->add('store', ChoiceType::class, [
                'choices' => array_flip(StoreEnum::toArray())
            ])
            ->add('url', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', null)
            ->add('store', ChoiceType::class, [
                'choices' => array_flip(StoreEnum::toArray())
            ])
            ->add('url', null)
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
}