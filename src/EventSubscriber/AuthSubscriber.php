<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\Login;
use App\Api\Dto\Registration;
use App\Entity\User;
use App\Exception\AuthException;
use App\Security\AccessToken;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AuthSubscriber implements EventSubscriberInterface
{
    /** @var UserPasswordEncoderInterface */
    protected $passwordEncoder;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var AccessToken */
    protected $accessToken;

    public function __construct(UserPasswordEncoderInterface $encoder, EntityManagerInterface $em, AccessToken $at)
    {
        $this->passwordEncoder = $encoder;
        $this->entityManager = $em;
        $this->accessToken = $at;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['registerUser', EventPriorities::POST_VALIDATE],
            KernelEvents::VIEW => ['authenticateUser', EventPriorities::POST_VALIDATE]
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

        try {

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $event->setResponse(new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT));

        } catch (UniqueConstraintViolationException $ex) {
            throw new AuthException('Email already exists', 0, $ex);
        }
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     * @throws AuthException
     */
    public function authenticateUser(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();
        if ('api_logins_post_collection' !== $request->attributes->get('_route')) {
            return;
        }

        /** @var Login $data */
        $data = $event->getControllerResult();

        /** @var User $user */
        $user = $this->entityManager->getRepository('App:User')->findOneBy(['email' => $data->email]);
        if (null === $user || !$this->passwordEncoder->isPasswordValid($user, $data->password)) {
            throw new AuthException('Invalid credentials');
        }

        $event->setResponse(new JsonResponse(
            ['token' => $this->accessToken->create($user->getEmail())],
            JsonResponse::HTTP_CREATED
        ));
    }
}