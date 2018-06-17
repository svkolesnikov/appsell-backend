<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserType extends AbstractType
{
    /** @var UserPasswordEncoderInterface */
    protected $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email',      TextType::class,        ['required' => true])
            ->add('password',   HiddenType::class)
            ->add('profile',    UserProfileType::class)
            ->add('is_active',  null,                   ['mapped' => false])
            ->add('groups',     null,                   ['label' => 'Группы'])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $user = $event->getData();
            $form = $event->getForm();

            $plainPassword = $user['plainPassword'];
            if (!empty($plainPassword)) {
                $user['password'] = $this->passwordEncoder->encodePassword($form->getData(), $plainPassword);
            }

            $event->setData($user);
            $form->getData()->setActive($user['is_active'] ?? false, true);
        });

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $user = $event->getData();
            $form = $event->getForm();

            $options = ( !$user || null === $user->getId())
                ? ['required' => true, 'mapped' => false, 'label' => 'Пароль']
                : ['required' => false, 'mapped' => false, 'label' => 'Пароль'];

            $form->add('plainPassword', PasswordType::class, $options);

            $form->get('is_active')->setData($user->isActive());
        });
    }
}