<?php

namespace App\Controller\Api;

//use App\Lib\Http\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
//use App\Swagger\Annotations\CurrentUserDefinition;
use function Swagger\scan;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @SWG\Swagger(
 *
 *      basePath = "/api",
 *      schemes = {"http"},
 *      produces = {"application/json"},
 *
 *      @SWG\Info(
 *          title ="AppSell API",
 *          version = "1.0"
 *      )
 * )
 */
class SwaggerController
{
    /**
     * @Route("/swagger", methods = { "GET" })
     */
    public function getAction(): JsonResponse
    {
        $swagger = scan(__DIR__);
        return new JsonResponse($swagger, JsonResponse::HTTP_OK, [], true);
    }
}