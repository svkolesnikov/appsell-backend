<?php

namespace App\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\ApiInfoSchema;

class StartController
{
    /**
     * @SWG\Get(
     *
     *  path = "/",
     *  summary = "Информация об API",
     *  description = "",
     *  tags = { "Info" },
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "",
     *      @ApiInfoSchema()
     *  )
     * )
     *
     * @Route("/", methods = { "GET" })
     */
    public function indexAction(): JsonResponse
    {
        return new JsonResponse([
            'oferta' => 'https://appsell.me'
        ]);
    }
}