<?php

namespace App\Controller\Api;

use App\DataSource\SellerPromoCodeDataSource;
use App\Entity\Offer;
use App\Entity\OfferLink;
use App\Entity\PromoCode;
use App\Entity\User;
use App\Exception\Api\FormValidationException;
use App\Lib\Enum\OfferLinkTypeEnum;
use App\Lib\Enum\OfferTypeEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use chillerlan\QRCode\QRCode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\OfferStatisticSchema;
use App\Swagger\Annotations\OfferForSellerSchema;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\NotFoundResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/sellers")
 */
class SellerPromoCodeController
{
    /** @var SellerPromoCodeDataSource */
    protected $dataSource;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var UserGroupManager */
    protected $groupManager;

    public function __construct(SellerPromoCodeDataSource $ds, TokenStorageInterface $ts, EntityManagerInterface $em, UserGroupManager $gm)
    {
        $this->dataSource = $ds;
        $this->tokenStorage = $ts;
        $this->entityManager = $em;
        $this->groupManager = $gm;
    }

    /**
     * @SWG\Get(
     *
     *  path = "/sellers/promo-codes/{id}/get-lik",
     *  summary = "Доступные офферы для продавцов",
     *  description = "",
     *  tags = { "Sellers" },
     *
     *  @TokenParameter(),
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "Список получен",
     *      @SWG\Schema(
     *          type = "array",
     *          items = @OfferForSellerSchema()
     *      )
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse()
     * )
     *
     * @Route("/promo-codes/{id}/get-link", methods = { "GET" })
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \App\Exception\Api\DataSourceException
     * @throws FormValidationException
     */
    public function getAvailablePromoCodeAction(Request $request, string $id): JsonResponse
    {
        try {

            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();

            if (!$this->groupManager->hasGroup($user, UserGroupEnum::SELLER())) {
                throw new AccessDeniedHttpException('Sellers only access');
            }

            /** @var Offer $offer */
            $offer = $this->entityManager->find('App:Offer', $id);
            if (!$offer) {
                throw new NotFoundHttpException('Оффер не найден');
            }

            $clickId = $request->get('clickId');

            if (!$clickId) {
                throw new AccessDeniedHttpException('Click id not found');
            }

            $promoCode = $this->dataSource->getRandomFreshPromoCode($offer);

            if (!$promoCode) {
                throw new AccessDeniedHttpException('Promo code not found');
            }

            $promoCode->setUser($user);

            $offerLink = new OfferLink();

            $offerLink->setOffer($promoCode->getOffer());
            $offerLink->setType(OfferLinkTypeEnum::PROMO_CODE());

            $clickId = $request->get('clickId');

            $url = $this->dataSource->getPromoCodeShowUrl($promoCode, $clickId);
            $offerLink->setUrl(str_replace(['%28', '%29'], ['(', ')'], $url));

            $this->entityManager->persist($offerLink);
            $this->entityManager->persist($promoCode);
            $this->entityManager->flush();

            return new JsonResponse([
                'url' => $url,
                'qrCode' => (new QRCode())->render($url),
            ]);

        } catch (\UnexpectedValueException $ex) {
            throw new FormValidationException(
                'Передан неверный параметр',
                ['type' => 'Допустимые значения: ' . implode(', ', OfferTypeEnum::toArray())]
            );
        }
    }
}