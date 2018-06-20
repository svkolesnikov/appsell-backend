<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\NotFoundResponse;
use App\Swagger\Annotations\UserSchema;
use App\Swagger\Annotations\TokenParameter;

/**
 * @Route("/users")
 */
class UserController
{
    public function __construct()
    {
    }

    /**
     * @SWG\Get(
     *
     *  path = "/users/current",
     *  summary = "Получение профиля текущего пользователя",
     *  description = "",
     *  tags = { "Users" },
     *
     *  @TokenParameter(),
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "Профиль получен",
     *      @UserSchema()
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @NotFoundResponse(),
     * )
     *
     * @Route("/current", methods = { "POST" })
     * @return JsonResponse
     */
    public function getCurrentUserAction(): JsonResponse
    {

    }
}