<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\Token;
use App\Entity\User;
use App\Security\AccessToken;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TokenSubscriber implements EventSubscriberInterface
{
    /** @var AccessToken */
    protected $accessToken;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(AccessToken $at, TokenStorageInterface $ts)
    {
        $this->accessToken = $at;
        $this->tokenStorage = $ts;
    }

    public static function getSubscribedEvents(): array
    {
        return [
//            KernelEvents::VIEW => [
////                ['refreshToken', EventPriorities::POST_VALIDATE]
//            ]
        ];
    }

    public function refreshToken(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();
        if ('api_tokens_post_collection' !== $request->attributes->get('_route')) {
            return;
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var Token $data */
        $data = $event->getControllerResult();
        $data->token   = $this->accessToken->create($user->getEmail());
        $data->user_id = $user->getId();

        $event->setResponse(new JsonResponse($data, JsonResponse::HTTP_CREATED));
    }
}