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

class UserAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'settings/users';

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
                ->add('email', null, ['required' => true, 'label' => 'Email'])
                ->add('profile.firstname', null, ['required' => false, 'label' => 'First Name'])
                ->add('profile.lastname', null, ['required' => false, 'label' => 'Last Name'])
                ->add('profile.phone', null, ['required' => false, 'label' => 'Phone'])
                ->add('password', HiddenType::class)
                ->add('plainPassword', TextType::class, array(
                    'mapped' => false,
                    'required' => (!$this->getSubject() || is_null($this->getSubject()->getId())),
                    'label' => 'Пароль',
                    'data' => ''
                ))
                ->add('is_active', CheckboxType::class, array('required' => false, 'label' => 'Активен'))
//                ->add('profile', AdminType::class, [
//                    'label' => '',
////                    'attr' => [
////                        'class'     => 'col-md-6 box box-solid box-primary',
////                    ]
//                ])
            ->end()
            ->with('Группы')
                ->add('groups', ModelType::class, [
                    'required' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'label' => 'Группы'
                ])
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
            ->add('email', null)
            ->add('groups', null)
            ->add('_action', null, [
                'label' => 'Действия',
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ]
            ])
        ;
    }

    public function prePersist($object)
    {
        $this->encodePassword($object);
        parent::prePersist($object); // TODO: Change the autogenerated stub
    }

    public function preUpdate($object)
    {
        $this->encodePassword($object);
        parent::preUpdate($object); // TODO: Change the autogenerated stub
    }

    private function encodePassword($object)
    {
        $uniqid = $this->getRequest()->query->get('uniqid');
        $data = new ParameterBag($this->getRequest()->request->get($uniqid));
        $plainPasswd = $data->get('plainPassword');

        if (empty($plainPasswd)) {
            return;
        }

        /** @var UserPasswordEncoderInterface $encoder */
        $encoder = $this
            ->getConfigurationPool()
            ->getContainer()
            ->get('security.password_encoder');

         $object->setPassword($encoder->encodePassword($object, $plainPasswd));
    }
}