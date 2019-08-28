<?php

namespace App\Controller\Api;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/appsflyer")
 */
class AppsFlyerPostbackController
{
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route(methods = {"GET"}, path = "/postbacks/install")
     * @param Request $request
     * @return Response
     */
    public function installPostbackAction(Request $request)
    {
        $this->logger->info('AppsFlyer install postback', [
            'query' => $request->query->all(),
            'remote_addr' => $request->getClientIp()
        ]);

        return new Response('', Response::HTTP_CREATED);
    }

    /**
     * @Route(methods = {"GET"}, path = "/postbacks/in-app-event")
     * @param Request $request
     * @return Response
     */
    public function inAppEventPostbackAction(Request $request)
    {
        $this->logger->info('AppsFlyer in app event postback', [
            'query' => $request->query->all(),
            'remote_addr' => $request->getClientIp()
        ]);

        return new Response('', Response::HTTP_CREATED);
    }
}