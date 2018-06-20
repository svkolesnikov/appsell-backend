<?php

namespace App\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class StartController
{
    /**
     * @Route("/", methods = { "GET" })
     */
    public function indexAction(): JsonResponse
    {
        return new JsonResponse();
    }
}