<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\EmailConfirmation;
use App\Api\Dto\Login;
use App\Api\Dto\Token;
use App\Entity\ConfirmationCode;
use App\Entity\User;
use App\Exception\AuthException;
use App\Security\AccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class EmailConfirmationSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [['confirmEmail', EventPriorities::POST_VALIDATE]]
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     * @throws \UnexpectedValueException
     * @throws AuthException
     */
    public function confirmEmail(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();
        if ('api_email_confirmations_post_collection' !== $request->attributes->get('_route')) {
            return;
        }

        /** @var EmailConfirmation $data */
        $data = $event->getControllerResult();

        /** @var User $user */
        $user = $this->entityManager->getRepository('App:User')->findOneBy(['email' => $data->email]);
        if (null === $user) {
            throw new AuthException(sprintf('Пользователь с адресом %s не зарегистрирован', $data->email));
        }

        /** @var ConfirmationCode[] $confirmations */
        $confirmations = $this->entityManager->getRepository('App:ConfirmationCode')->findBy(['subject' => $data->email], ['ctime' => 'desc'], 1);
        if (0 === \count($confirmations)) {
            throw new AuthException('На указанный адрес не отправлялся код подтверждения');
        }

        if ($confirmations[0]->getCode() !== $data->code) {
            throw new AuthException('Неверный код подтверждения');
        }

        $confirmations[0]->setConfirmed(true);
        $user->setActive(true);

        $this->entityManager->persist($confirmations[0]);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $event->setResponse(new JsonResponse(null, JsonResponse::HTTP_CREATED));
    }
}