<?php

namespace App\Form;

use App\Entity\Repository\UserRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;

class EmployeesCsvImportType extends AbstractType
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->userRepository = $em->getRepository(User::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $employers = $this->userRepository->findEmployeers();

        $keys = array_map(fn (User $u) => $u->getId(), $employers);
        $values = array_map(fn (User $u) => sprintf('%s (%s)', $u->getProfile()->getCompanyTitle(), $u->getProfile()->getCompanyId()), $employers);

        $builder
            ->add('employeer_id', ChoiceType::class,      [
                'required'    => true,
                'label'       => 'Работодатель',
                'placeholder' => 'Не выбрано',
                'choices'     => array_combine($values, $keys),
            ])
            ->add('file', FileType::class, [
                'required' => true,
                'label'    => 'Файл с сотрудниками (email,password)',
            ])
        ;
    }
}