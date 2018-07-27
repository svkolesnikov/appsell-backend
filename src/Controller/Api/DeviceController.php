<?php

namespace App\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\DeviceTokenSchema;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/users")
 */
class DeviceController
{
    /**
     * @SWG\Post(
     *
     *  path = "/users/current/devices/push-tokens",
     *  summary = "Добавление нового токена для push-уведомлений",
     *  description = "",
     *  tags = { "Users" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body", @DeviceTokenSchema()),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Токен для push-уведомлений добавлен"
     *  ),
     *
     *  @BadRequestResponse(),
     *  @AccessDeniedResponse(),
     *  @UnauthorizedResponse()
     * )
     *
     * @Route("/current/devices/push-tokens", methods = { "POST" })
     * @param Request $request
     * @return JsonResponse
     */
    public function createPushTokenAction(Request $request): JsonResponse
    {

    }
}