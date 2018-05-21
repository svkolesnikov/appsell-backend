<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\Employee;
use App\Api\Dto\Owner;
use App\Api\Dto\Seller;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Exception\AuthException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationSubscriber implements EventSubscriberInterface
{
    /** @var UserPasswordEncoderInterface */
    protected $passwordEncoder;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(UserPasswordEncoderInterface $encoder, EntityManagerInterface $em, TokenStorageInterface $ts)
    {
        $this->passwordEncoder = $encoder;
        $this->entityManager = $em;
        $this->tokenStorage = $ts;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [
                ['registerUser', EventPriorities::POST_VALIDATE]
            ]
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     * @throws AuthException
     */
    public function registerUser(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();

        if ('api_employees_post_collection' === $request->attributes->get('_route')) {
            $this->registerEmployee($event->getControllerResult());
            $event->setResponse(new JsonResponse(null, JsonResponse::HTTP_CREATED));
        }

        if ('api_sellers_post_collection' === $request->attributes->get('_route')) {
            $this->registerSeller($event->getControllerResult());
            $event->setResponse(new JsonResponse(null, JsonResponse::HTTP_CREATED));
        }

        if ('api_owners_post_collection' === $request->attributes->get('_route')) {
            $this->registerOwner($event->getControllerResult());
            $event->setResponse(new JsonResponse(null, JsonResponse::HTTP_CREATED));
        }
    }

    /**
     * @param Owner $form
     * @throws AuthException
     */
    protected function registerOwner(Owner $form): void
    {
        // todo: отправлять письмо с подтверждением email?
        // todo: добавить в группу "Владельцы приложения"

        $user = new User();
        $user->setEmail($form->email);

        $profile = new UserProfile();
        $profile->setUser($user);
        $profile->setPhone($form->phone);

        $this->save($profile);
    }

    /**
     * @param Seller $form
     * @throws AuthException
     */
    protected function registerSeller(Seller $form): void
    {
        // todo: отправлять письмо с подтверждением email?
        // todo: добавить в группу "Продавцы"

        $user = new User();
        $user->setEmail($form->email);

        $profile = new UserProfile();
        $profile->setUser($user);
        $profile->setPhone($form->phone);

        $this->save($profile);
    }

    /**
     * @param Employee $form
     * @throws AuthException
     */
    protected function registerEmployee(Employee $form): void
    {
        // todo: что-то делать с $form->code
        // todo: отправлять письмо с кодом проверки email
        // todo: добавить в группу "Продавцы" и привязать к конторе

        $user = new User();
        $user->setEmail($form->email);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $form->password));

        $profile = new UserProfile();
        $profile->setUser($user);

        $this->save($profile);
    }

    /**
     * @param UserProfile $profile
     * @throws AuthException
     */
    protected function save(UserProfile $profile): void
    {
        try {

            $this->entityManager->persist($profile);
            $this->entityManager->persist($profile->getUser());
            $this->entityManager->flush();

        } catch (UniqueConstraintViolationException $ex) {
            throw new AuthException('Email already exists', $ex);
        }
    }
}