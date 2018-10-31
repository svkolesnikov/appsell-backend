<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
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
     * @param Request $request
     * @return JsonResponse
     */
    public function indexAction(Request $request): JsonResponse
    {
        $docsUrl = sprintf(
            '%s://%s/docs',
            $request->server->get('HTTP_SCHEME'),
            $request->server->get('HTTP_HOST')
        );

        return new JsonResponse([
            'end_user_agreement' => $docsUrl . '/end_user_agreement.pdf',
            'privacy_policy'     => $docsUrl . '/privacy_policy.pdf',
            'terms_of_use'       => $docsUrl . '/terms_of_use.pdf'
        ]);
    }
}