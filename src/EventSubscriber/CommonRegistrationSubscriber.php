<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\Owner;
use App\Api\Dto\Seller;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Enum\NotificationTypeEnum;
use App\Exception\AuthException;
use App\Notification\Producer\SystemProducer;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CommonRegistrationSubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => [
                ['registerUser', EventPriorities::POST_VALIDATE]
            ]
        ];
    }

    public function registerUser(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();

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
     * Регистрация "заказчика"
     * Это компания-владелец некоего приложения, которая хочет,
     * чтобы ее приложение пиарили и устанавливаели
     *
     * @param Owner $form
     */
    protected function registerOwner(Owner $form): void
    {
        $user = new User();
        $user->setEmail($form->email);

        $profile = $user->getProfile();
        $profile->setPhone($form->phone);

        try {
            $this->save($profile);
        } catch (AuthException $ex) {
            // В данном случае может возникнуть ситуация, что про заявку все забыли
            // а чувак снова пытается зарегаться - так что, если у нас уже есть
            // этот пользователь - вышлем уведомление повторно
        }

        $this->systemProducer->produce(NotificationTypeEnum::NEW_OWNER(), [
            'subject' => 'Зарегистрировался новый заказчик',
            'email'   => $form->email,
            'phone'   => $form->phone
        ]);
    }

    /**
     * @param Seller $form
     */
    protected function registerSeller(Seller $form): void
    {
        $user = new User();
        $user->setEmail($form->email);

        $profile = $user->getProfile();
        $profile->setPhone($form->phone);

        try {
            $this->save($profile);
        } catch (AuthException $ex) {
            // В данном случае может возникнуть ситуация, что про заявку все забыли
            // а чувак снова пытается зарегаться - так что, если у нас уже есть
            // этот пользователь - вышлем уведомление повторно
        }

        $this->systemProducer->produce(NotificationTypeEnum::NEW_SELLER(), [
            'subject' => 'Зарегистрировался новый продавец',
            'email'   => $form->email,
            'phone'   => $form->phone
        ]);
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