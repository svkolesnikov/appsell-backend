<?php

namespace App\DCI;

use App\Entity\User;
use App\Exception\Admin\ImportFromCsvException;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ImportEmployeesFromCsv
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private UserGroupManager $groupManager;
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(
        EntityManagerInterface $em,
        UserGroupManager $gm,
        UserPasswordEncoderInterface $encoder,
        LoggerInterface $logger
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->groupManager = $gm;
        $this->passwordEncoder = $encoder;
    }

    public function import(string $employeerId, UploadedFile $file): void
    {
        $usersRepository = $this->em->getRepository(User::class);

        // Получим работодателя

        /** @var User $employer */
        $employer = $this->em->getRepository(User::class)->find($employeerId);
        if (null === $employer || !$this->groupManager->hasGroup($employer, UserGroupEnum::SELLER())) {
            throw new ImportFromCsvException("Работодатель $employeerId не входит в группу компаний-продавцов");
        }

        // пробуем разобрать файл

        $csvFile = new \SplFileObject($file->getPath() . '/' . $file->getFilename());
        $csvFile->setCsvControl();

        while ($cells = $csvFile->fgetcsv()) {

            // Не удалось разобрать строку
            if (\count($cells) < 2) {
                continue;
            }

            $email = $cells[0];
            $password = $cells[1];

            // Проверим, что пользователя еще нет
            $existsUser = $usersRepository->findOneBy(['email' => $email]);
            if (null !== $existsUser) {
                continue;
            }

            // Поехали создавать новых
            try {
                $this->em->beginTransaction();

                // Пользователь
                $user = new User();
                $user->setEmail(strtolower($email));
                $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
                $user->setActive(true);

                // Добавление в группу сотрудников
                $this->groupManager->addGroup($user, UserGroupEnum::EMPLOYEE());

                $confirmation = $user->getConfirmation();
                $confirmation->setEmail($user->getEmail());
                $confirmation->setEmailConfirmed(true);
                $confirmation->setEmailConfirmationTime(new \DateTime());

                $profile = $user->getProfile();
                $profile->setEmployer($employer);

                $this->em->persist($profile->getUser());
                $this->em->flush();

                $this->em->commit();
            } catch (\Exception $ex) {
                $this->em->rollback();
                throw $ex;
            }
        }
    }
}