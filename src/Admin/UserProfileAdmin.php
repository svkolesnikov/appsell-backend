<?php

namespace App\Admin;

use App\Entity\UserProfile;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserProfileAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'settings/user-profiles';

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('profile.firstname', null, ['required' => false, 'label' => 'First Name'])
            ->add('profile.lastname', null, ['required' => false, 'label' => 'Last Name'])
            ->add('profile.phone', null, ['required' => false, 'label' => 'Phone']);
    }
}