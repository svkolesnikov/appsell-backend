<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\SellerOfferSchema;

/**
 * @Route("/sellers")
 */
class SellerOfferController
{
    /**
     * @SWG\Post(
     *
     *  path = "/sellers/offers",
     *  summary = "Доступные офферы для продавцов",
     *  description = "",
     *  tags = { "Offers" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "limit", default = 20, in = "query", type = "integer"),
     *  @SWG\Parameter(name = "after", description = "ID оффера, после которого загружать список", in = "query", type = "string"),
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "Список получен",
     *      @SWG\Schema(
     *          type = "array",
     *          items = @SellerOfferSchema()
     *      )
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse()
     * )
     *
     * @Route("/offers", methods = { "GET" })
     * @return JsonResponse
     */
    public function getAvailableOffersAction(): JsonResponse
    {

    }
}