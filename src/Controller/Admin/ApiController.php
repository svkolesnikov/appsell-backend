<?php

namespace App\Controller\Admin;

use App\Manager\CommissionManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends BaseController
{
    /** @var  EntityManagerInterface */
    protected $em;

    /** @var  CommissionManager  */
    protected $commissionManager;

    public function __construct(
        EntityManagerInterface $em,
        CommissionManager $commissionManager
    )
    {
        $this->em = $em;
        $this->commissionManager = $commissionManager;
    }

    /**
     * @Route("/admin/api/commissions/base", name="api_commissions_seller_update")
     * @Method({"POST"})
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateSellerCommissionAction(Request $request): JsonResponse
    {
        $commission = $request->get('value');
        if ($commission < 0 || $commission > 100) {
            return new JsonResponse (
                ['error' => 'Комиссия должна быть в диапазоне от 0 до 100'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->commissionManager->updateSellerBaseCommission($this->getUser(), $commission);

        } catch (\Exception $ex) {
            return new JsonResponse (
                ['error' => 'Не удалось обновить комисиию: ' . $ex->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse([], JsonResponse::HTTP_OK);
    }
}