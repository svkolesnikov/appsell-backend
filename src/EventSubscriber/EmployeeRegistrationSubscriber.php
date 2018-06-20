<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\Employee;
use App\Entity\ConfirmationCode;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Lib\Enum\NotificationTypeEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Exception\AuthException;
use App\Notification\Producer\ClientProducer;
use App\Security\UserGroupManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class EmployeeRegistrationSubscriber implements EventSubscriberInterface
{
    /** @var UserPasswordEncoderInterface */
    protected $passwordEncoder;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var UserGroupManager */
    protected $groupManager;

    /** @var ClientProducer */
    protected $clientProducer;

    public function __construct(UserPasswordEncoderInterface $encoder, EntityManagerInterface $em, UserGroupManager $gm, ClientProducer $cp)
    {
        $this->passwordEncoder = $encoder;
        $this->entityManager = $em;
        $this->groupManager = $gm;
        $this->clientProducer = $cp;
    }

    public static function getSubscribedEvents(): array
    {
        return [
//            KernelEvents::VIEW => [['registerUser', EventPriorities::POST_VALIDATE]]
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     * @throws AuthException
     * @throws \App\Exception\AppException
     * @throws \Exception
     */
    public function registerUser(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();
        if ('api_employees_post_collection' !== $request->attributes->get('_route')) {
            return;
        }

        /** @var Employee $form */
        $form = $event->getControllerResult();

        /** @var UserProfile $employer */
        $employer = $this->entityManager->getRepository('App:UserProfile')->findOneBy(['company_id' => $form->company_id]);
        if (null === $employer) {
            throw new AuthException('Неверный идентификатор компании');
        }

        // Сгенерируем код подтверждения

        $confirmationCode = new ConfirmationCode();
        $confirmationCode->setSubject($form->email);
        $confirmationCode->setCode(random_int(111111, 999999));
        $this->entityManager->persist($confirmationCode);

        // Создадим пользователя

        $user = new User();
        $user->setEmail($form->email);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $form->password));
        $this->groupManager->addGroup($user, UserGroupEnum::EMPLOYEE());

        $profile = $user->getProfile();
        $profile->setEmployer($employer->getUser());

        try {

            $this->entityManager->persist($profile);
            $this->entityManager->persist($profile->getUser());
            $this->entityManager->flush();

        } catch (UniqueConstraintViolationException $ex) {
            throw new AuthException('Email already exists', $ex);
        }

        // Отправим уведомление с кодом

        $this->clientProducer->produce(NotificationTypeEnum::CONFIRM_EMAIL(), [
            'subject' => 'Код активации email на сервисе AppSell',
            'code' => $confirmationCode->getCode()
        ]);

        $event->setResponse(new JsonResponse(null, JsonResponse::HTTP_CREATED));
    }
}