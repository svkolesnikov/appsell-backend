<?php

namespace App\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Swagger\Annotations\BadRequestResponse;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/registration")
 */
class RegistrationController
{
    public function __construct()
    {
    }

    /**
     * @SWG\Post(
     *
     *  path = "/registration/sellers",
     *  summary = "Регистрация продавца",
     *  description = "",
     *  tags = { "Registration" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", type = "object", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "phone" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "phone", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Пользователь зарегистрирован"
     *  ),
     *
     *  @BadRequestResponse()
     * )
     *
     * @Route("/sellers", methods = { "POST" })
     */
    public function registerSellerAction(): JsonResponse
    {

    }

    /**
     * @SWG\Post(
     *
     *  path = "/registration/owners",
     *  summary = "Регистрация заказчика",
     *  description = "",
     *  tags = { "Registration" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", type = "object", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "phone" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "phone", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Пользователь зарегистрирован"
     *  ),
     *
     *  @BadRequestResponse()
     * )
     *
     * @Route("/sellers", methods = { "POST" })
     */
    public function registerOwnerAction(): JsonResponse
    {

    }

    /**
     * @SWG\Post(
     *
     *  path = "/registration/employees",
     *  summary = "Регистрация сотрудника продавца",
     *  description = "",
     *  tags = { "Registration" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", type = "object", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "phone" , "company_id" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "password", type = "string"),
     *          @SWG\Property(property = "company_id", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Пользователь зарегистрирован"
     *  ),
     *
     *  @BadRequestResponse()
     * )
     *
     * @Route("/sellers", methods = { "POST" })
     */
    public function registerEmployeeAction(): JsonResponse
    {

    }
}