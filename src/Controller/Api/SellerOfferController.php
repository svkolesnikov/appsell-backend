<?php

namespace App\Controller\Api;

use App\DataSource\SellerOfferDataSource;
use App\Entity\User;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\SellerOfferSchema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/sellers")
 */
class SellerOfferController
{
    /** @var SellerOfferDataSource */
    protected $dataSource;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(SellerOfferDataSource $ds, TokenStorageInterface $ts)
    {
        $this->dataSource = $ds;
        $this->tokenStorage = $ts;
    }

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
     *  @SWG\Parameter(name = "offset", default = 0, in = "query", type = "integer"),
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
     * @param Request $request
     * @param UserGroupManager $groupManager
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \App\Exception\Api\DataSourceException
     */
    public function getAvailableOffersAction(Request $request, UserGroupManager $groupManager): JsonResponse
    {
        /** @var User $user */
        $user   = $this->tokenStorage->getToken()->getUser();
        $limit  = (int) $request->get('limit', 20);
        $offset = (int) $request->get('offset', 0);

        if (!$groupManager->hasGroup($user, UserGroupEnum::SELLER())) {
            throw new AccessDeniedHttpException('Sellers only access');
        }

        return new JsonResponse(array_map(function (array $item) {

            $compensations = (array) json_decode($item['compensations'], true);
            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'description' => $item['description'],
                'type' => $item['type'],
                'compensations' => array_map(function (array $comp) {
                    return [
                        'type' => $comp['type'],
                        'description' => $comp['description'],
                        'currency' => $comp['currency'],
                        'price' => $comp['price']
                    ];
                }, $compensations)
            ];

        }, $this->dataSource->getAvailableOffers($user, $limit, $offset)));
    }
}