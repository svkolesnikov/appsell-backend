<?php
namespace App\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class GroupType extends AbstractType
{
    protected $roles;

    public function __construct(ContainerInterface $container)
    {
        $this->roles = $this->getRoleList($container->getParameter('security.role_hierarchy.roles'));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['required' => true])
            ->add('roles', ChoiceType::class, [
                'choices' => array_flip($this->roles),
                'expanded' => true,
                'multiple' => true
            ])
        ;
    }

    protected function getRoleList(array $roleHierarchy)
    {
        $roles = [];
        foreach ($roleHierarchy as $key => $role) {

            if (\is_array($role)) {
                $roles = array_merge($this->getRoleList($role), $roles);
                $roles[$key] = sprintf('%s: %s', $key, implode(', ', $role));

            } else {
                $roles[$role] = $role;
            }
        }
        return $roles;
    }
}