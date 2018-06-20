<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Api\Dto\UserOfferLink;
use App\Exception\AppException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Entity;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserOfferLinkSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var RouterInterface */
    protected $router;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(EntityManagerInterface $em, RouterInterface $router, TokenStorageInterface $ts)
    {
        $this->entityManager = $em;
        $this->router = $router;
        $this->tokenStorage = $ts;
    }

    public static function getSubscribedEvents(): array
    {
        return [
//            KernelEvents::VIEW => []
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     * @throws AppException
     */
    public function createLink(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();
        if ('api_user_offer_links_post_collection' !== $request->attributes->get('_route')) {
            return;
        }

        /** @var UserOfferLink $data */
        $data = $event->getControllerResult();

        /** @var Entity\Offer $offer */
        $offer = $this->entityManager->find('App:Offer', $data->offer_id);
        if (null === $offer) {
            throw new AppException(sprintf('Оффер %s не найден', $data->offer_id));
        }

        /** @var Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        // Получим ссылку на оффер или создадим новую

        $offerLink = $this->entityManager->getRepository('App:UserOfferLink')->findOneBy([
            'user'  => $user,
            'offer' => $offer
        ]);

        if (null === $offerLink) {
            $offerLink = new Entity\UserOfferLink();
            $offerLink->setUser($user);
            $offerLink->setOffer($offer);

            $this->entityManager->persist($offerLink);
            $this->entityManager->flush();
        }

        $data->url = $this->router->generate(
            'follow_user_offer_link',
            ['id' => $offerLink->getId()],
            RouterInterface::ABSOLUTE_URL
        );

        $event->setResponse(new JsonResponse(
            ['url' => $data->url],
            JsonResponse::HTTP_CREATED)
        );
    }
}