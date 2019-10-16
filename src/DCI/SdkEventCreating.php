<?php

namespace App\DCI;

use App\Entity\BaseCommission;
use App\Entity\Compensation;
use App\Entity\EventType;
use App\Entity\ForOfferCommission;
use App\Entity\ForUserCommission;
use App\Entity\Offer;
use App\Entity\OfferExecution;
use App\Entity\OfferLink;
use App\Entity\SdkEvent;
use App\Entity\SellerBaseCommission;
use App\Entity\User;
use App\Entity\UserOfferLink;
use App\Exception\Api\EventExistsException;
use App\Exception\Api\EventWithBadDataException;
use App\Exception\Api\EventWithoutReferrerException;
use App\Exception\Api\SolarStaffException;
use App\Lib\Enum\CommissionEnum;
use App\Lib\Enum\CurrencyEnum;
use App\Lib\Enum\OfferExecutionStatusEnum;
use App\Lib\Enum\SdkEventSourceEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use App\SolarStaff\Client;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Query\Expr\Join;

class SdkEventCreating
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var UserGroupManager */
    protected $userGroupManager;

    /** @var Client */
    protected $solarStaffClient;

    public function __construct(EntityManagerInterface $em, UserGroupManager $gm, Client $solarStaffClient)
    {
        $this->entityManager = $em;
        $this->userGroupManager = $gm;
        $this->solarStaffClient = $solarStaffClient;
    }

    public function isClickId(string $reffererId): bool
    {
        return 0 === strpos($reffererId, 'click-');
    }

    public function extractClickId(string $clickId): string
    {
        return str_replace('click-', '', $clickId);
    }

    /**
     * @param string $clickId OfferExecuteion->Id
     * @param string $eventName
     * @param SdkEventSourceEnum $source
     * @param array $requestInfo
     * @return SdkEvent
     * @throws EntityNotFoundException
     * @throws EventWithoutReferrerException
     * @throws EventExistsException
     */
    public function createFromClickId(string $clickId, string $eventName, SdkEventSourceEnum $source, array $requestInfo = []): SdkEvent
    {
        $clickId = $this->extractClickId($clickId);

        /** @var OfferExecution $offerExecution */
        $offerExecution = $this->entityManager->getRepository(OfferExecution::class)->find($clickId);
        if (null === $offerExecution) {
            throw new EventWithoutReferrerException('Передан некорректный clickid (offer_execution.id)');
        }

        /** @var Compensation $compensation */
        $compensation = $this->entityManager->getRepository('App:Compensation')->findOneBy([
            'offer'      => $offerExecution->getOffer(),
            'event_type' => $eventName
        ]);

        if (null === $compensation) {
            throw new EntityNotFoundException('Событие отсутствует в оффере');
        }

        /** @var EventType $eventType */
        $eventType = $this->entityManager->find(EventType::class, $eventName);
        $employee  = $offerExecution->getSourceLink()->getUser();
        $deviceId  = sprintf('%s-%s', $source->getValue(), $clickId);

        // Начнем сохранение

        $this->entityManager->beginTransaction();

        try {

            // Далее рассчитываем суммы (если необходимо), для конкретного события
            // и всех участником схемы
            // А необходимо только если передан referrer_id и он является сотрудником продавца

            $newEvent = $this->create(
                $offerExecution,
                $offerExecution->getOfferLink(),
                $eventType,
                $employee,
                $compensation,
                $deviceId,
                $source,
                [],
                $requestInfo
            );

            // Все збс, можно закрывать транзакцию

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $newEvent;

        } catch (\Exception $ex) {

            // Какая бы ошибка ни произошла, необходимо откатить транзакцию
            // и следом прокинуть исключение дальше
            $this->entityManager->rollback();
            throw $ex;
        }
    }

    /**
     * @param string $eventName
     * @param string $appId
     * @param string $deviceId
     * @param null|string $referrerId
     * @param array $eventData
     * @param array $requestInfo
     * @return SdkEvent
     * @throws EntityNotFoundException
     * @throws EventWithBadDataException
     * @throws EventWithoutReferrerException
     * @throws SolarStaffException
     * @throws EventExistsException
     */
    public function createFromSdk(
        string $eventName,
        string $appId,
        string $deviceId,
        ?string $referrerId,
        array $eventData = [],
        array $requestInfo = []
    ): SdkEvent
    {
        // Проверим наличие оффера, ссылки и ивента в нем

        /** @var OfferLink $link */
        $link = $this->entityManager->find('App:OfferLink', $appId);
        if (null === $link) {
            throw new EntityNotFoundException('Приложение не найдено');
        }

        /** @var Compensation $compensation */
        $compensation = $this->entityManager->getRepository('App:Compensation')->findOneBy([
            'offer'      => $link->getOffer(),
            'event_type' => $eventName
        ]);

        if (null === $compensation) {
            throw new EntityNotFoundException('Событие отсутствует в оффере');
        }

        // Проверим наличие сотрудника
        // Если не нашли сотрудника по ID, то событие запишется в БД
        // без ссылки на пользователя, с 0 суммами, и не будет влиять
        // на выплаты и статистику

        /** @var User $employee */
        $employee = $referrerId ? $this->entityManager->find('App:User', $referrerId) : null;
        if (null === $employee) {
            throw new EventWithoutReferrerException('ReferrerId отсутствует в присланом событии');
        }

        if (isset($eventData['email'])) {

            // При получении событий с email, чекать что email не принадлежит автору ссылки,
            // по которой был переход. чтобы отсеить тех, что сам себе накручивает

            /** @var User $eventAuthor */
            $eventAuthor = $this->entityManager
                ->getRepository('App:User')
                ->findOneBy(['email' => $eventData['email']]);

            // Если по email ничего не нашли
            // то и проверять нечего, т. к. пользователи могут быть вполне себе
            // не из нашей системы

            if (null !== $eventAuthor) {
                if ($eventAuthor->getId() === $employee->getId()) {
                    throw new EventWithBadDataException("Попытка фрода – пользователь {$eventAuthor->getEmail()} установил приложение по своей ссылке");
                }

                // При получении события login, если в нем есть email, который принадлежит челику,
                // зареганному через солар, проверять что челик в соларе активировался,
                // иначе событие логина не принимать

                if ('login' === $eventName) {
                    if ($eventAuthor->getProfile()->isSolarStaffConnected()) {
                        if (!$this->solarStaffClient->isWorkerRegSuccess($eventAuthor->getEmail())) {
                            throw new EventWithBadDataException("Попытка фрода – пользователь {$eventAuthor->getEmail()} не завершил регистрацию в SolarStaff");
                        }
                    }
                }
            }
        }

        // Вытащим еще и событие
        // Проверять, получили ли мы сам ивент смысла нет, тк до этого мы получили с
        // его участием компенсацию, а мы бы этого не смогли, если бы ивент не существовал
        // это гарантируют ключи в БД

        /** @var EventType $eventType */
        $eventType = $this->entityManager->find('App:EventType', $eventName);

        // Начнем сохранение

        $this->entityManager->beginTransaction();

        try {

            // Получим ссылку на OfferExecution
            // если нет ни одного "свободного", то будем его создавать
            // при этом, если не был передан referrer_id, то можно создать
            // формальный OfferExecution, без привязки к реферальной ссылке
            // а суммы в event записать нулевые

            $qb = $this->entityManager->createQueryBuilder();
            $qb = $qb
                ->select('e')
                ->from('App:OfferExecution', 'e')
                ->join('e.source_link', 'ul', Join::WITH)
                ->leftJoin('e.events', 'ee', Join::WITH, 'ee.event_type = :event_type')
                ->setMaxResults(1)
                ->where('e.offer = :offer')
                ->andWhere('e.status = :status')
                ->andWhere('e.offer_link = :app_link')
                ->andWhere('ee.id is null')
                ->andWhere('ul.user = :employee')
                ->orderBy('e.ctime', 'asc')
                ->setParameters([
                    'offer'      => $link->getOffer(),
                    'app_link'   => $link,
                    'event_type' => $eventType,
                    'employee'   => $employee,
                    'status'     => OfferExecutionStatusEnum::PROCESSING
                ]);

            /** @var OfferExecution $offerExecution */
            $offerExecution = $qb->getQuery()->getOneOrNullResult();

            if (null === $offerExecution) {

                // Вообще, если пришел ивент с referrer_id, значит ссылка была
                // так что просто пытаемся ее достать, для привязки к ней очередного
                // исполнения оффера

                /** @var UserOfferLink $userOfferLink */
                $userOfferLink = $this->entityManager->getRepository('App:UserOfferLink')->findOneBy([
                    'user'  => $employee,
                    'offer' => $link->getOffer()
                ]);

                $offerExecution = new OfferExecution();
                $offerExecution->setOfferLink($link);
                $offerExecution->setOffer($link->getOffer());
                $offerExecution->setSourceLink($userOfferLink);
                $offerExecution->setStatus(OfferExecutionStatusEnum::PROCESSING());

                $offerExecution->setSourceReferrerInfo(array_intersect_key(
                    $requestInfo,
                    array_flip([
                        'HTTP_USER_AGENT',
                        'REMOTE_ADDR'
                    ])
                ));

                $offerExecution->setSourceReferrerFingerprint(md5(
                    $requestInfo['HTTP_USER_AGENT'] .
                    $requestInfo['REMOTE_ADDR']
                ));

                $this->entityManager->persist($offerExecution);
            }

            // Далее рассчитываем суммы (если необходимо), для конкретного события
            // и всех участником схемы
            // А необходимо только если передан referrer_id и он является сотрудником продавца

            $newEvent = $this->create(
                $offerExecution,
                $link,
                $eventType,
                $employee,
                $compensation,
                $deviceId,
                SdkEventSourceEnum::APP(),
                $eventData,
                $requestInfo
            );

            // Все збс, можно закрывать транзакцию

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $newEvent;

        } catch (\Exception $ex) {

            // Какая бы ошибка ни произошла, необходимо откатить транзакцию
            // и следом прокинуть исключение дальше
            $this->entityManager->rollback();
            throw $ex;
        }
    }

    /**
     * Увеличивает сумму выплаченных компенсаций на размер
     * текущей компенсации. Если после этого бюджет оказывается
     * превышенным - деактивируем оффер
     *
     * @param Offer $offer
     * @param Compensation $compensation
     * @throws DBALException
     */
    protected function increaseOfferBudget(Offer $offer, Compensation $compensation): void
    {
        $sql = <<<SQL
UPDATE offerdata.offer
SET payed_amount = (coalesce(payed_amount, 0) + :price), 
    is_active    = budget >= (coalesce(payed_amount, 0) + :price) 
WHERE id = :offer_id
SQL;

        $this->entityManager->getConnection()->executeQuery($sql, [
            'price' => $compensation->getPrice(),
            'offer_id' => $offer->getId()
        ]);

        $this->entityManager->refresh($offer);
    }

    /**
     * @param OfferExecution $offerExecution
     * @param OfferLink $link
     * @param EventType $eventType
     * @param User $employee
     * @param Compensation $compensation
     * @param string $deviceId
     * @param SdkEventSourceEnum $eventSource
     * @param array $eventData
     * @param array $requestInfo
     * @return SdkEvent
     * @throws DBALException
     * @throws EventExistsException
     */
    protected function create(
        OfferExecution $offerExecution,
        OfferLink $link,
        EventType $eventType,
        User $employee,
        Compensation $compensation,
        string $deviceId,
        SdkEventSourceEnum $eventSource,
        array $eventData = [],
        array $requestInfo = []
    ): SdkEvent
    {
        $amountForService = 0;
        $amountForSeller = 0;
        $amountForEmployee = 0;
        $amountForPayout = 0;

        if (null !== $employee && $this->userGroupManager->hasGroup($employee, UserGroupEnum::EMPLOYEE())) {

            $employer = $employee->getProfile()->getEmployer();
            if (null !== $employer) {

                // Рассчет комиссии сервиса

                /** @var ForOfferCommission $serviceForOfferCommission */
                $serviceForOfferCommission = $this->entityManager
                    ->getRepository('App:ForOfferCommission')
                    ->findOneBy(['offer' => $link->getOffer(), 'by_user' => null]);

                /** @var ForUserCommission $serviceForUserCommission */
                $serviceForUserCommission = $this->entityManager
                    ->getRepository('App:ForUserCommission')
                    ->findOneBy(['user' => $employer, 'by_user' => null]);

                /** @var BaseCommission $serviceBaseCommission */
                $serviceBaseCommission = $this->entityManager
                    ->getRepository('App:BaseCommission')
                    ->findOneBy(['type' => CommissionEnum::SERVICE]);

                if (null !== $serviceForOfferCommission) {
                    $servicePercent = $serviceForOfferCommission->getPercent();
                } elseif (null !== $serviceForUserCommission) {
                    $servicePercent = $serviceForUserCommission->getPercent();
                } elseif (null !== $serviceBaseCommission) {
                    $servicePercent = $serviceBaseCommission->getPercent();
                } else {
                    $servicePercent = 0;
                }

                $amountForService = round($compensation->getPrice() * $servicePercent / 100, 2);

                // Рассчет комиссии компании

                /** @var ForOfferCommission $serviceForOfferCommission */
                $sellerForOfferCommission = $this->entityManager
                    ->getRepository('App:ForOfferCommission')
                    ->findOneBy(['offer' => $link->getOffer(), 'by_user' => $employer]);

                /** @var SellerBaseCommission $sellersBaseCommission */
                $sellersBaseCommission = $this->entityManager
                    ->getRepository('App:SellerBaseCommission')
                    ->findOneBy(['seller' => $employer]);

                /** @var BaseCommission $sellerBaseCommission */
                $sellerBaseCommission = $this->entityManager
                    ->getRepository('App:BaseCommission')
                    ->findOneBy(['type' => CommissionEnum::SELLER]);

                if (null !== $sellerForOfferCommission) {
                    $sellerPercent = $sellerForOfferCommission->getPercent();
                } elseif (null !== $sellersBaseCommission) {
                    $sellerPercent = $sellersBaseCommission->getPercent();
                } elseif (null !== $sellerBaseCommission) {
                    $sellerPercent = $sellerBaseCommission->getPercent();
                } else {
                    $sellerPercent = 0;
                }

                $amountForSeller = round(($compensation->getPrice() - $amountForService) * $sellerPercent / 100, 2);

                // Рассчет комиссии, которую забирает SolarStaff при выводе
                // Рассчитываем только в случае если пользователь зарегистрирован в SS
                // и его компания-продавец выводит средства через SS

                if ($employee->getProfile()->isSolarStaffConnected() && $employer->getProfile()->isCompanyPayoutOverSolarStaff()) {

                    /** @var BaseCommission $payoutBaseCommission */
                    $payoutBaseCommission = $this->entityManager
                        ->getRepository('App:BaseCommission')
                        ->findOneBy(['type' => CommissionEnum::SOLAR_STAFF_PAYOUT]);

                    if (null !== $payoutBaseCommission) {
                        $payoutPercent = $payoutBaseCommission->getPercent();
                        $amountForPayout = round(($compensation->getPrice() - $amountForService - $amountForSeller) * $payoutPercent / 100, 2);
                    }
                }

                // Сумма для сотрудника

                $amountForEmployee = $compensation->getPrice() - $amountForService - $amountForSeller - $amountForPayout;
            }
        }

        // А вот теперь начинаем формировать событие для сохранения

        $sourceInfo = [
            'event_data' => $eventData,
            'request' => array_intersect_key(
                $requestInfo,
                array_flip([
                    'HTTP_USER_AGENT',
                    'REMOTE_ADDR'
                ])
            )
        ];

        $newEvent = new SdkEvent();
        $newEvent->setEventType($eventType);
        $newEvent->setDeviceId($deviceId);
        $newEvent->setCurrency(CurrencyEnum::RUB());
        $newEvent->setAmountForService($amountForService);
        $newEvent->setAmountForSeller($amountForSeller);
        $newEvent->setAmountForEmployee($amountForEmployee);
        $newEvent->setAmountForPayout($amountForPayout);
        $newEvent->setOffer($link->getOffer());
        $newEvent->setOfferLink($link);
        $newEvent->setSource($eventSource);
        $newEvent->setEmployee($employee);
        $newEvent->setSourceInfo($sourceInfo);

        $offerExecution->addEvent($newEvent);

        // Перед тем как попробовать записать в БД, проверим, нет ли
        // там уже этого события

        $existsEvent = $this->entityManager->getRepository(SdkEvent::class)->findOneBy([
            'offer'      => $link->getOffer(),
            'offer_link' => $link,
            'device_id'  => $deviceId,
            'event_type' => $eventType
        ]);

        if (null !== $existsEvent) {

            $offerId = $link->getOffer()->getId();
            $eventCode = $eventType->getCode();

            throw new EventExistsException("Событие $eventCode от $deviceId для $offerId уже было записано");
        }

        $this->entityManager->persist($newEvent);

        // А теперь бы еще понять, изменился ли статус OfferExecution
        // по нему могли прийти все допустимые события и он завершился

        $compensationEvents = $link->getOffer()->getCompensations()->map(function (Compensation $c) {
            return $c->getEventType()->getCode();
        })->toArray();

        $sdkEvents = $offerExecution->getEvents()
            ->map(function (SdkEvent $e) {
                return $e->getEventType()->getCode();
            })->toArray();

        sort($compensationEvents);
        sort($sdkEvents);

        $isExecutionComplete = $compensationEvents === $sdkEvents
            && $offerExecution->getStatus()->equals(OfferExecutionStatusEnum::PROCESSING());

        if ($isExecutionComplete) {

            $offer = $link->getOffer();
            $this->entityManager->refresh($offer);

            // При наличии бюджета на оффер нужно определить превысили мы в него или нет
            // Если превысили, то нужно деактивировать оффер

            if ($offer->isActive() && $offer->hasBudget()) {
                if (!$offer->isBudgetExceeded()) {

                    $this->increaseOfferBudget($offer, $compensation);

                } else {

                    // Бюджет превысили уже, но оффер до сих пор активный
                    // Деактивируем его в срочном порядке!

                    $offer->setActive(false);
                    $this->entityManager->persist($offer);
                }
            }

            $offerExecution->setStatus($offer->isActive() ? OfferExecutionStatusEnum::COMPLETE() : OfferExecutionStatusEnum::REJECTED());
            $this->entityManager->persist($offerExecution);
        }

        return $newEvent;
    }
}