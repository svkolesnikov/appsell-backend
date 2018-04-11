<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\Registration;
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
        if ('api_registrations_post_collection' !== $request->attributes->get('_route')) {
            return;
        }

        /** @var Registration $data */
        $data = $event->getControllerResult();

        $user = new User();
        $user->setEmail($data->email);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $data->password));

        $profile = new UserProfile();
        $profile->setUser($user);

        try {

            $this->entityManager->persist($profile);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $event->setResponse(new JsonResponse(null, JsonResponse::HTTP_CREATED));

        } catch (UniqueConstraintViolationException $ex) {
            throw new AuthException('Email already exists', 0, $ex);
        }
    }
}