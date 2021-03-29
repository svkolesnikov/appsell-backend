<?php

namespace App\DataSource;

use App\DataSource\Dto\ReportItem;
use App\DataSource\Dto\StatisticItem;
use App\DataSource\Dto\SellerOffer;
use App\Entity\Offer;
use App\Entity\OfferExecution;
use App\Entity\PromoCode;
use App\Entity\Repository\PromoCodeRepository;
use App\Entity\User;
use App\Exception\Api\DataSourceException;
use App\Lib\Enum\OfferExecutionStatusEnum;
use App\Lib\Enum\OfferTypeEnum;
use App\Service\ImageService;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class SellerPromoCodeDataSource
{
    /** @var RouterInterface */
    protected $router;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var ImageService */
    protected $imageService;

    /** @var PromoCodeRepository */
    protected $promoCodeRepository;

    public function __construct(RouterInterface $router, EntityManagerInterface $em, ImageService $imageService)
    {
        $this->router = $router;
        $this->entityManager = $em;
        $this->imageService = $imageService;
        $this->promoCodeRepository = $em->getRepository(PromoCode::class);
    }

    public function getRandomFreshPromoCode(Offer $offer)
    {
        return $this->promoCodeRepository->getRandomFresh($offer);
    }

    public function getPromoCodeShowUrl(PromoCode $promoCode, $clickId): string
    {
        $time = time();

        return $this->router->generate('external_promo_codes_show', [
            'id' => $promoCode->getId(),
            'time' => $time,
            'hash' => md5($promoCode->getId() . $time . 'external_promo_codes_show'),
            'clickId' => $clickId
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}