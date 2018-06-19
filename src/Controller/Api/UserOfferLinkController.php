<?php

namespace App\Controller\Api;

use App\Entity\OfferExecution;
use App\Entity\OfferLink;
use App\Entity\UserOfferLink;
use App\Enum\OfferLinkTypeEnum;
use App\Enum\OfferTypeEnum;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use BrowserDetection;
use Symfony\Component\Templating\EngineInterface;

class UserOfferLinkController
{
    protected $entityManager;

    /** @var BrowserDetection */
    protected $browser;

    /** @var EngineInterface */
    protected $templating;

    public function __construct(EntityManagerInterface $em, EngineInterface $templating)
    {
        $this->entityManager = $em;
        $this->browser = new BrowserDetection();
        $this->templating = $templating;
    }

    /**
     * @Route(methods = {"GET"}, path = "/api/usl/{id}", name = "follow_user_offer_link")
     * @param Request $request
     * @return Response
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function followLinkAction(Request $request): Response
    {
        // todo: Сделать возможным переход для неактивного оффера и логировать это отдельно

        /** @var UserOfferLink $userOfferLink */
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

        if ($offer->getType()->equals(OfferTypeEnum::APP())) {

            // Либо тип ссылки передали в запросе, либо пытаемся
            // определить ее по UserAgent'у

            if ($requestedType === OfferLinkTypeEnum::GOOGLE_PLAY()->getValue() || \in_array($this->browser->getPlatform(), $googlePlayPlatforms, true)) {
                $linkType = OfferLinkTypeEnum::GOOGLE_PLAY();
            }

            if ($requestedType === OfferLinkTypeEnum::APP_STORE()->getValue() || \in_array($this->browser->getPlatform(), $appStorePlatforms, true)) {
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

                        $execution = new OfferExecution();
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

        return new Response($this->templating->render(
            'api/UserOfferLink/followLink.html.twig',
            ['offer' => $offer, 'followLink' => $userOfferLink]
        ));
    }
}