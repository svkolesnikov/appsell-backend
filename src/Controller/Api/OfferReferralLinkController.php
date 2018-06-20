<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\NotFoundResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\ReferralLinkSchema;

/**
 * @Route("/offers")
 */
class OfferReferralLinkController
{
    public function __construct()
    {
    }

    /**
     * @SWG\Post(
     *
     *  path = "/offers/{id}/referral-links",
     *  summary = "Создание реферальной ссылки на оффер для текущего пользователя",
     *  description = "",
     *  tags = { "Offers" },
     *
     *  @TokenParameter(),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Ссылка создана",
     *      @ReferralLinkSchema()
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @NotFoundResponse()
     * )
     *
     * @Route("/{id}/referral-links", methods = { "POST" })
     * @return JsonResponse
     */
    public function createLinkController(): JsonResponse
    {

    }
}