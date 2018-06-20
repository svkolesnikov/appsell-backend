<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\EmployeeOfferSchema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/employees")
 */
class EmployeeOfferController
{
//    /** @var SellerOfferDataSource */
//    protected $dataSource;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $ts)
    {
//        $this->dataSource = $ds;
        $this->tokenStorage = $ts;
    }

    /**
     * @SWG\Post(
     *
     *  path = "/employees/offers",
     *  summary = "Доступные офферы для сотрудников продавцов",
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
     *          items = @EmployeeOfferSchema()
     *      )
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse()
     * )
     *
     * @Route("/employees", methods = { "GET" })
     * @param Request $request
     * @param UserGroupManager $groupManager
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function getAvailableOffersAction(Request $request, UserGroupManager $groupManager): JsonResponse
    {
        /** @var User $user */
        $user   = $this->tokenStorage->getToken()->getUser();
        $limit  = (int) $request->get('limit', 20);
        $offset = (int) $request->get('offset', 0);

        if (!$groupManager->hasGroup($user, UserGroupEnum::EMPLOYEE())) {
            throw new AccessDeniedHttpException('Employees only access');
        }

        return new JsonResponse([]);
    }
}