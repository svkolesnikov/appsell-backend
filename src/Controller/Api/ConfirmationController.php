<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\NotFoundResponse;
use App\Swagger\Annotations\TokenParameter;

/**
 * @Route("/confirmations")
 */
class ConfirmationController
{
    public function __construct()
    {
    }

    /**
     * @SWG\Post(
     *
     *  path = "/confirmations/email",
     *  summary = "Подтверждение email кодом",
     *  description = "",
     *  tags = { "Confirmations" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", type = "object", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "code" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "code", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 202,
     *      description = "Email подтвержден"
     *  ),
     *
     *  @BadRequestResponse(),
     *  @NotFoundResponse()
     * )
     *
     * @Route("/email", methods = { "POST" })
     * @return JsonResponse
     */
    public function confirmEmailAction(): JsonResponse
    {

    }
}