<?php

namespace App\Controller\Api;

use App\Entity;
use App\Lib\Enum\OfferLinkTypeEnum;
use App\Lib\Enum\OfferTypeEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use BrowserDetection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\NotFoundResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\ReferralLinkSchema;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Templating\EngineInterface;
use Doctrine\ORM\Query\Expr;

class OfferReferralLinkController
{
    protected $entityManager;

    /** @var RouterInterface */
    protected $router;

    public function __construct(EntityManagerInterface $em, RouterInterface $router)
    {
        $this->entityManager = $em;
        $this->router = $router;
    }

    /**
     * @SWG\Post(
     *
     *  path = "/employees/offers/{id}/referral-links",
     *  summary = "Создание реферальной ссылки на оффер для текущего пользователя",
     *  description = "",
     *  tags = { "Employees" },
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
     * @Route("/employees/offers/{id}/referral-links", methods = { "POST" })
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param UserGroupManager $gm
     * @return JsonResponse
     */
    public function createLinkController(Request $request, TokenStorageInterface $tokenStorage, UserGroupManager $gm): JsonResponse
    {
        /** @var Entity\User $user */
        $user = $tokenStorage->getToken()->getUser();
        if (!$gm->hasGroup($user, UserGroupEnum::EMPLOYEE())) {
            throw new AccessDeniedHttpException('Employees only access');
        }

        /** @var Entity\Offer $offer */
        $offer = $this->entityManager->find('App:Offer', $request->get('id'));
        if (null === $offer) {
            throw new NotFoundHttpException(sprintf('Оффер %s не найден', $request->get('id')));
        }

        // Получим ссылку на оффер или создадим новую

        $offerLink = $this->entityManager->getRepository('App:UserOfferLink')->findOneBy([
            'user'  => $user,
            'offer' => $offer
        ]);

        if (null === $offerLink) {
            $offerLink = new Entity\UserOfferLink();
            $offerLink->setUser($user);
            $offerLink->setOffer($offer);

            $this->entityManager->persist($offerLink);
            $this->entityManager->flush();
        }

        $url = $this->router->generate('app_api_offer_referral_link_follow', ['id' => $offerLink->getId()], RouterInterface::ABSOLUTE_URL);
        return new JsonResponse(['url' => $url], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route(methods = {"GET"}, path = "/referral-links/{id}", name = "app_api_offer_referral_link_follow")
     * @param Request $request
     * @param EngineInterface $templating
     * @return Response
     *
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws DBALException
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function followLinkAction(Request $request, EngineInterface $templating): Response
    {
        // todo: Сделать возможным переход для неактивного оффера и логировать это отдельно

        /** @var Entity\UserOfferLink $userOfferLink */
        $userOfferLink = $this->entityManager->createQueryBuilder()
            ->select('l, o')
            ->from('App:UserOfferLink', 'l')
            ->join('l.offer', 'o', Expr\Join::WITH)
            ->where('l.id = :id and o.is_active = true and o.is_deleted = false')
            ->setParameter('id', $request->get('id'))
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $userOfferLink) {
            throw new NotFoundHttpException();
        }

        $appStorePlatforms   = [BrowserDetection::PLATFORM_IPHONE, BrowserDetection::PLATFORM_IPAD, BrowserDetection::PLATFORM_IPOD];
        $googlePlayPlatforms = [BrowserDetection::PLATFORM_ANDROID, BrowserDetection::PLATFORM_BLACKBERRY];

        $requestedType = $request->get('type');
        $redirectTo    = null;
        $linkType      = null;
        $offer         = $userOfferLink->getOffer();
        $browser       = new BrowserDetection();

        if ($offer->getType()->equals(OfferTypeEnum::APP())) {

            // Либо тип ссылки передали в запросе, либо пытаемся
            // определить ее по UserAgent'у

            if ($requestedType === OfferLinkTypeEnum::GOOGLE_PLAY()->getValue() || \in_array($browser->getPlatform(), $googlePlayPlatforms, true)) {
                $linkType = OfferLinkTypeEnum::GOOGLE_PLAY();
            }

            if ($requestedType === OfferLinkTypeEnum::APP_STORE()->getValue() || \in_array($browser->getPlatform(), $appStorePlatforms, true)) {
                $linkType = OfferLinkTypeEnum::APP_STORE();
            }
        }

        if ($offer->getType()->equals(OfferTypeEnum::SERVICE())) {
            $linkType = OfferLinkTypeEnum::WEB();
        }

        if (null !== $linkType) {
            foreach ($offer->getLinks() as $link) {
                if ($link->getType()->equals($linkType)) {

                    $connection = $this->entityManager->getConnection();
                    $connection->beginTransaction();

                    try {

                        // Обновим кол-во использований ссылки

                        $sql = <<<SQL
update actiondata.user_offer_link 
set 
  usage_count = usage_count + 1,
  mtime = now() 
where id = :id
SQL;

                        $statement = $connection->prepare($sql);
                        $statement->execute(['id' => $userOfferLink->getId()]);

                        // Создадим индикатор начала исполнения оффера

                        $execution = new Entity\OfferExecution();
                        $execution->setOffer($offer);
                        $execution->setOfferLink($link);
                        $execution->setSourceLink($userOfferLink);
                        $execution->setSourceReferrerInfo($_SERVER);

                        $this->entityManager->persist($execution);
                        $this->entityManager->flush();

                        $connection->commit();

                    } catch (DBALException $ex) {
                        $connection->rollBack();
                        throw $ex;
                    }

                    // Обнаружили ссылку, переходим
                    return new RedirectResponse($link->getUrl());
                }
            }

            throw new NotFoundHttpException('Не задана ссылка на приложение');
        }

        // ссылку определить не удалось
        // покажем список доступных ссылок для перехода

        return new Response($templating->render(
            'api/UserOfferLink/followLink.html.twig',
            ['offer' => $offer, 'followLink' => $userOfferLink]
        ));
    }
}