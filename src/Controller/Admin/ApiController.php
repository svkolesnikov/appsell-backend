<?php

namespace App\Controller\Admin;

use App\Entity\ForOfferCommission;
use App\Entity\ForUserCommission;
use App\Entity\Offer;
use App\Entity\SellerBaseCommission;
use App\Entity\User;
use App\Exception\Admin\OfferNotFoundException;
use App\Exception\Admin\UserNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends BaseController
{
    /** @var  EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route(
     *     "/admin/api/commissions/seller",
     *     methods = { "POST" },
     *     name="api_commissions_seller_base"
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSellerCommissionAction(Request $request): JsonResponse
    {
        $percent = $request->get('value');
        if ($percent < 0 || $percent > 100) {
            return new JsonResponse (
                ['error' => 'Комиссия должна быть в диапазоне от 0 до 100'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            $commission = $this->em
                ->getRepository(SellerBaseCommission::class)
                ->findOneBy(['seller' => $this->getUser()]);

            $commission = $commission ?? (new SellerBaseCommission())->setSeller($this->getUser());
            $commission->setPercent($percent);

            $this->em->persist($commission);
            $this->em->flush();

        } catch (\Exception $ex) {
            return new JsonResponse (
                ['error' => 'Не удалось обновить комисиию: ' . $ex->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse([], JsonResponse::HTTP_OK);
    }

    /**
     * @Route(
     *     "/admin/api/commissions/offer/{id}/{by_user}",
     *     methods = { "POST" },
     *     name="api_commissions_for_offer",
     *     defaults={"by_user"=null}
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateForOfferCommissionAction(Request $request): JsonResponse
    {
        $percent = $request->get('value');
        if ($percent < 0 || $percent > 100) {
            return new JsonResponse (
                ['error' => 'Комиссия должна быть в диапазоне от 0 до 100'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        /** @var User $byUser */
        $byUser   = null;
        $byUserId = $request->get('by_user');

        try {

            if (null !== $byUserId) {
                $byUser = $this->em->getRepository(User::class)->find($byUserId);
                if (null === $byUser) {
                    throw new UserNotFoundException();
                }
            }

            /** @var Offer $offer */
            $offer = $this->em->getRepository(Offer::class)->find($request->get('id'));
            if (null === $offer) {
                throw new OfferNotFoundException();
            }

            $params = ['offer' => $offer, 'by_user' => $byUser];
            $commission = $this->em->getRepository(ForOfferCommission::class)->findOneBy($params);

            $commission = $commission ?? new ForOfferCommission();
            $commission
                ->setPercent($percent)
                ->setOffer($offer)
                ->setByUser($byUser);

            $this->em->persist($commission);
            $this->em->flush();

        } catch (\Exception $ex) {
            return new JsonResponse (
                ['error' => 'Не удалось обновить комисиию: ' . $ex->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse([], JsonResponse::HTTP_OK);
    }

    /**
     * @Route(
     *     "/admin/api/commissions/user/{id}/{by_user}",
     *     methods = { "POST" },
     *     name="api_commissions_for_user",
     *     defaults={"by_user"=null}
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateForUserCommissionAction(Request $request): JsonResponse
    {
        $percent = $request->get('value');
        if ($percent < 0 || $percent > 100) {
            return new JsonResponse (
                ['error' => 'Комиссия должна быть в диапазоне от 0 до 100'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        /** @var User $byUser */
        $byUser   = null;
        $byUserId = $request->get('by_user');

        try {

            if (null !== $byUserId) {
                $byUser = $this->em->getRepository(User::class)->find($byUserId);
                if (null === $byUser) {
                    throw new UserNotFoundException();
                }
            }

            /** @var User $user */
            $user = $this->em->getRepository(User::class)->find($request->get('id'));
            if (null === $user) {
                throw new UserNotFoundException();
            }

            $params = ['user' => $user, 'by_user' => $byUser];
            $commission = $this->em
                ->getRepository(ForUserCommission::class)
                ->findOneBy($params);

            $commission = $commission ?? new ForUserCommission();
            $commission->setPercent($percent)
                ->setUser($user)
                ->setByUser($byUser);

            $this->em->persist($commission);
            $this->em->flush();

        } catch (\Exception $ex) {
            return new JsonResponse (
                ['error' => 'Не удалось обновить комисиию: ' . $ex->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse([], JsonResponse::HTTP_OK);
    }
}