<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\EmailConfirmation;
use App\Entity\ConfirmationCode;
use App\Entity\User;
use App\Lib\Enum\NotificationTypeEnum;
use App\Exception\AuthException;
use App\Notification\Producer\SystemProducer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EmailConfirmationSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var SystemProducer */
    protected $systemProducer;

    public function __construct(EntityManagerInterface $em, SystemProducer $sp)
    {
        $this->entityManager = $em;
        $this->systemProducer = $sp;
    }

    public static function getSubscribedEvents(): array
    {
        return [
//            KernelEvents::VIEW => [['confirmEmail', EventPriorities::POST_VALIDATE]]
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

        // Отправим уведомление о регистрации сотрудника

        $employer = $user->getProfile()->getEmployer();

        $this->systemProducer->produce(NotificationTypeEnum::NEW_EMPLOYEE(), [
            'subject' => 'Зарегистрировался новый сотрудник продавца',
            'email'   => $data->email,
            'company' => $employer ? $employer->getProfile()->getCompanyTitle() : null
        ]);

        $event->setResponse(new JsonResponse(null, JsonResponse::HTTP_CREATED));
    }
}