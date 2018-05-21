<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\OfferApp;
use App\Entity\SellerOfferLink;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class OfferLinkSuscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => [
                ['createOrFetchLink', EventPriorities::POST_VALIDATE],
                ['redirectToStore', EventPriorities::PRE_VALIDATE]
            ]
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     * @throws NotFoundHttpException
     */
    public function redirectToStore(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();

        print_r($request->attributes->all());
        die();
//        echo $request->attributes->get('_route');
//        die();

        if ('api_seller_offer_links_redirect_to_store_item' === $request->attributes->get('_route')) {

            /** @var SellerOfferLink $link */
            $link = $this->entityManager->getRepository('App:SellerOfferLink')->find($request->get('id'));

            if (null === $link) {
                throw new NotFoundHttpException('Link not found');
            }

            die('123');
        }
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     * @throws NotFoundHttpException
     */
    public function createOrFetchLink(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();
        if ('api_seller_offer_links_create_link_collection' === $request->attributes->get('_route')) {

            /** @var OfferApp $app */
            $app = $this->entityManager->getRepository('App:OfferApp')->find($request->get('id'));

            /** @var User $seller */
            $seller = $this->entityManager->getRepository('App:User')->find($request->get('seller_id'));

            if (null === $app || null === $seller) {
                throw new NotFoundHttpException('Seller or App not found');
            }

            // Сначала пробуем найти уже существующую ссылку

            /** @var SellerOfferLink $link */
            $link = $this->entityManager->getRepository('App:SellerOfferLink')->findOneBy([
                'offer_app' => $app,
                'seller' => $seller
            ]);

            if (null === $link) {
                $link = new SellerOfferLink();
                $link->setOfferApp($app);
                $link->setSeller($seller);

                $this->entityManager->persist($link);
                $this->entityManager->flush();
            }

            /** @var \App\Api\Dto\SellerOfferLink $response */
            $response = $event->getControllerResult();
            $response->url = $link->getId();

            $event->setResponse(new JsonResponse($response, JsonResponse::HTTP_CREATED));
        }
    }
}