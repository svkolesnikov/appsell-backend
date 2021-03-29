<?php

namespace App\Controller\External;

use App\DataSource\SellerPromoCodeDataSource;
use App\Entity\OfferExecution;
use App\Entity\OfferLink;
use App\Entity\PromoCode;
use App\Entity\Repository\PromoCodeRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Exception\Api\FormValidationException;
use App\Lib\Enum\OfferLinkTypeEnum;
use App\Lib\Enum\OfferTypeEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use App\Service\ImageService;
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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/sellers")
 */
class SellerPromoCodeController
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var PromoCodeRepository */
    protected $promoCodeRepository;

    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->entityManager = $em;
        $this->promoCodeRepository = $em->getRepository(PromoCode::class);
    }

    /**
     * @Route("/promo-codes/show/{id}", methods = { "GET" }, name="external_promo_codes_show")
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \App\Exception\Api\DataSourceException
     * @throws FormValidationException
     */
    public function handleLinkPromoCodeAction(Request $request, string $id): JsonResponse
    {
        /** @var PromoCode $promoCode */
        $promoCode = $this->promoCodeRepository->find($id);

        if (!$promoCode) {
            throw new AccessDeniedHttpException('Promo code not found');
        }
        if ($promoCode->getStatus() !== PromoCode::STATUS_FRESH) {
            throw new AccessDeniedHttpException('Promo code not found');
        }

        $promoCode->setStatus(PromoCode::STATUS_SETTLE);

        $clickId = $request->get('clickId');

//        ??????????????????????????????????????????

//        $offerExecution = new OfferExecution();

//        $offerExecution->setOffer($promoCode->getOffer());
//        $offerExecution->set
//        $offerExecution->setOfferLink($promoCode);
//        $offerExecution->setSourceLink($request->getUri());

        $this->entityManager->persist($promoCode);
        $this->entityManager->flush();

        die('Промо код - ' . $promoCode->getPromoCode());
    }
}