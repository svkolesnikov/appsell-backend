<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class GroupAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'settings/groups';

    public function getBatchActions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', null, ['label' => 'Идентификатор'])
            ->add('name', null, ['label' => 'Наименование'])
            ->add('_action', null, [
                'label' => 'Действия',
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ]
            ])
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $hierarchy = $this
            ->getConfigurationPool()
            ->getContainer()
            ->getParameter('security.role_hierarchy.roles');

        $roles = $hierarchy;
        array_walk_recursive($hierarchy, function($role) use (&$roles) {
            $roles[$role] = $roles[$role] ?? $role;
        });

        foreach ($roles as $key => $value) {
            if (is_array($roles[$key])) {
                $roles[$key] = sprintf('%s: %s', $key, implode(', ', $value));
            }
        }

        $formMapper
            ->add('name')
            ->add('roles', ChoiceType::class, array(
                'choices' => array_flip($roles),
                'multiple' => true,
                'expanded' => true,
            ))
            ->end();
    }
}