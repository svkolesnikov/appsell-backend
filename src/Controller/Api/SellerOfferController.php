<?php

namespace App\Controller\Api;

use App\DataSource\SellerOfferDataSource;
use App\Entity\Offer;
use App\Entity\SellerApprovedOffer;
use App\Entity\User;
use App\Exception\Api\FormValidationException;
use App\Lib\Enum\OfferTypeEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\OfferWithCompensationsSchema;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\NotFoundResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var UserGroupManager */
    protected $groupManager;

    public function __construct(SellerOfferDataSource $ds, TokenStorageInterface $ts, EntityManagerInterface $em, UserGroupManager $gm)
    {
        $this->dataSource = $ds;
        $this->tokenStorage = $ts;
        $this->entityManager = $em;
        $this->groupManager = $gm;
    }

    /**
     * @SWG\Get(
     *
     *  path = "/sellers/offers",
     *  summary = "Доступные офферы для продавцов",
     *  description = "",
     *  tags = { "Sellers" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "type", in = "query", type = "string", description = "app или service"),
     *  @SWG\Parameter(name = "limit", default = 20, in = "query", type = "integer"),
     *  @SWG\Parameter(name = "offset", default = 0, in = "query", type = "integer"),
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "Список получен",
     *      @SWG\Schema(
     *          type = "array",
     *          items = @OfferWithCompensationsSchema()
     *      )
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse()
     * )
     *
     * @Route("/offers", methods = { "GET" })
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \App\Exception\Api\DataSourceException
     * @throws FormValidationException
     */
    public function getAvailableOffersAction(Request $request): JsonResponse
    {
        try {

            /** @var User $user */
            $user   = $this->tokenStorage->getToken()->getUser();
            $limit  = (int)$request->get('limit', 20);
            $offset = (int)$request->get('offset', 0);
            $type   = $request->get('type') ? new OfferTypeEnum($request->get('type')) : null;

            if (!$this->groupManager->hasGroup($user, UserGroupEnum::SELLER())) {
                throw new AccessDeniedHttpException('Sellers only access');
            }

            return new JsonResponse($this->dataSource->getAvailableOffers(
                $user,
                $limit,
                $offset,
                $type
            ));

        } catch (\UnexpectedValueException $ex) {
            throw new FormValidationException(
                'Передан неверный параметр',
                ['type' => 'Допустимые значения: ' . implode(', ', OfferTypeEnum::toArray())]
            );
        }
    }

    /**
     * @SWG\Post(
     *
     *  path = "/sellers/offers/{id}/employees/approval",
     *  summary = "Разрешение распространения оффера сотрудниками",
     *  description = "",
     *  tags = { "Sellers" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "id", type = "string", required = true, in = "path"),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Разрешено"
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @NotFoundResponse()
     * )
     *
     * @Route("/offers/{id}/employees/approval", methods = { "POST" })
     * @param Request $request
     * @return JsonResponse
     */
    public function approveAction(Request $request): JsonResponse
    {
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$this->groupManager->hasGroup($user, UserGroupEnum::SELLER())) {
            throw new AccessDeniedHttpException('Sellers only access');
        }

        /** @var Offer $offer */
        $offer = $this->entityManager->find('App:Offer', $request->get('id'));
        if (null === $offer) {
            throw new NotFoundHttpException('Оффер не найден');
        }

        $approval = $this->entityManager->getRepository('App:SellerApprovedOffer')->findOneBy([
            'offer'  => $offer,
            'seller' => $user
        ]);

        if (null === $approval) {
            $approval = new SellerApprovedOffer();
            $approval->setOffer($offer);
            $approval->setSeller($user);

            $this->entityManager->persist($approval);
            $this->entityManager->flush();
        }

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    /**
     * @SWG\Delete(
     *
     *  path = "/sellers/offers/{id}/employees/approval",
     *  summary = "Запрет распространения оффера сотрудниками",
     *  description = "",
     *  tags = { "Sellers" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "id", type = "string", required = true, in = "path"),
     *
     *  @SWG\Response(
     *      response = 204,
     *      description = "Запрещено"
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @NotFoundResponse()
     * )
     *
     * @Route("/offers/{id}/employees/approval", methods = { "DELETE" })
     * @param Request $request
     * @return JsonResponse
     */
    public function rejectAction(Request $request): JsonResponse
    {
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$this->groupManager->hasGroup($user, UserGroupEnum::SELLER())) {
            throw new AccessDeniedHttpException('Sellers only access');
        }

        /** @var Offer $offer */
        $offer = $this->entityManager->find('App:Offer', $request->get('id'));
        if (null === $offer) {
            throw new NotFoundHttpException('Оффер не найден');
        }

        $approval = $this->entityManager->getRepository('App:SellerApprovedOffer')->findOneBy([
            'offer'  => $offer,
            'seller' => $user
        ]);

        if (null !== $approval) {
            $this->entityManager->remove($approval);
            $this->entityManager->flush();
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}