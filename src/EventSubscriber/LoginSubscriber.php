<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\Login;
use App\Api\Dto\Token;
use App\Entity\User;
use App\Exception\AuthException;
use App\Security\AccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class LoginSubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => [
                ['authenticateUser', EventPriorities::POST_VALIDATE]
            ]
        ];
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

        $token = new Token();
        $token->token = $this->accessToken->create($user->getEmail());

        $event->setResponse(new JsonResponse($token, JsonResponse::HTTP_CREATED));
    }
}