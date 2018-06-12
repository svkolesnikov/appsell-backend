<?php

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class UserOfferLinkController
{
    protected $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @Route(methods = {"GET"}, path = "/api/usl/{id}", name = "follow_user_offer_link")
     * @param Request $request
     * @return Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function followLinkAction(Request $request): Response
    {
        $userOfferLink = $this->entityManager->find('App:UserOfferLink', $request->get('id'));
        if (null === $userOfferLink) {
            throw new NotFoundHttpException();
        }

        $browser = new \BrowserDetection();
        echo $browser->getPlatform() . '<br>';

        // todo: тречить переходы

        die('Здесь мы открываем заглушку выбора стора или сразу в стор отправляем');
    }
}