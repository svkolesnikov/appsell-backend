<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\Registration;
use App\Entity\User;
use App\Exception\EntityException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationSubscriber implements EventSubscriberInterface
{
    /** @var UserPasswordEncoderInterface */
    protected $passwordEncoder;

    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(UserPasswordEncoderInterface $encoder, EntityManagerInterface $em)
    {
        $this->passwordEncoder = $encoder;
        $this->entityManager = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['registerUser', EventPriorities::POST_VALIDATE]
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     * @throws EntityException
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
            throw new EntityException('Email already exists', 0, $ex);
        }
    }
}