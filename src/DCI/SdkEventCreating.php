<?php

namespace App\DCI;

use App\Entity\BaseCommission;
use App\Entity\Compensation;
use App\Entity\EventType;
use App\Entity\ForOfferCommission;
use App\Entity\ForUserCommission;
use App\Entity\OfferExecution;
use App\Entity\OfferLink;
use App\Entity\SdkEvent;
use App\Entity\SellerBaseCommission;
use App\Entity\User;
use App\Entity\UserOfferLink;
use App\Lib\Enum\CommissionEnum;
use App\Lib\Enum\CurrencyEnum;
use App\Lib\Enum\OfferExecutionStatusEnum;
use App\Lib\Enum\SdkEventSourceEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Query\Expr\Join;

class SdkEventCreating
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var UserGroupManager */
    protected $userGroupManager;

    public function __construct(EntityManagerInterface $em, UserGroupManager $gm)
    {
        $this->entityManager = $em;
        $this->userGroupManager = $gm;
    }

    /**
     * @param string $eventName
     * @param string $appId
     * @param string $deviceId
     * @param null|string $referrerId
     * @param array $requestInfo
     * @return SdkEvent
     * @throws \Exception
     */
    public function create(string $eventName, string $appId, string $deviceId, ?string $referrerId, array $requestInfo = []): SdkEvent
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
                ->leftJoin('e.events', 'ee', Join::WITH, 'ee.event_type = :event_type AND ee.device_id = :device_id')
                ->setMaxResults(1)
                ->where('e.offer = :offer')
                ->andWhere('e.offer_link = :app_link')
                ->andWhere('ee.id is null')
                ->setParameters([
                    'offer' => $link->getOffer(),
                    'app_link' => $link,
                    'event_type' => $eventType,
                    'device_id' => $deviceId
                ]);

            if (null !== $employee) {
                $qb = $qb
                    ->andWhere('ul.user = :employee')
                    ->setParameter('employee', $employee);
            }

            /** @var OfferExecution $offerExecution */
            $offerExecution = $qb->getQuery()->getOneOrNullResult();

            if (null === $offerExecution) {

                /** @var UserOfferLink $userOfferLink */
                $userOfferLink = null;
                if (null !== $employee) {

                    // Вообще, если пришел ивент с referrer_id, значит ссылка была
                    // так что просто пытаемся ее достать, для привязки к ней очередного
                    // исполнения оффера
                    $userOfferLink = $this->entityManager->getRepository('App:UserOfferLink')->findOneBy([
                        'user' => $employee,
                        'offer' => $link->getOffer()
                    ]);
                }

                $offerExecution = new OfferExecution();
                $offerExecution->setOfferLink($link);
                $offerExecution->setOffer($link->getOffer());
                $offerExecution->setSourceLink($userOfferLink);
                $offerExecution->setStatus(OfferExecutionStatusEnum::PROCESSING());

                $this->entityManager->persist($offerExecution);
            }

            // Далее рассчитываем суммы (если необходимо), для конкретного события
            // и всех участником схемы
            // А необходимо только если передан referrer_id и он является сотрудником продавца

            $amountForService = 0;
            $amountForSeller = 0;
            $amountForEmployee = 0;

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

                    // Сумма для сотрудника

                    $amountForEmployee = $compensation->getPrice() - $amountForService - $amountForSeller;
                }
            }

            // А вот теперь начинаем формировать событие для сохранения

            $newEvent = new SdkEvent();
            $newEvent->setEventType($eventType);
            $newEvent->setDeviceId($deviceId);
            $newEvent->setCurrency(CurrencyEnum::RUB());
            $newEvent->setAmountForService($amountForService);
            $newEvent->setAmountForSeller($amountForSeller);
            $newEvent->setAmountForEmployee($amountForEmployee);
            $newEvent->setOffer($link->getOffer());
            $newEvent->setOfferLink($link);
            $newEvent->setSource(SdkEventSourceEnum::APP());
            $newEvent->setEmployee($employee);
            $newEvent->setSourceInfo(array_intersect_key(
                $requestInfo,
                array_flip([
                    'HTTP_USER_AGENT',
                    'REMOTE_ADDR'
                ])
            ));

            $offerExecution->addEvent($newEvent);

            $this->entityManager->persist($newEvent);

            // А теперь бы еще понять, изменился ли статус OfferExecution
            // по нему могли прийти все допустимые события и он завершился

            $compensationEvents = $link->getOffer()->getCompensations()->map(function (Compensation $c) {
                return $c->getEventType()->getCode();
            })->toArray();

            $sdkEvents = $offerExecution->getEvents()->map(function (SdkEvent $e) {
                return $e->getEventType()->getCode();
            })->toArray();

            sort($compensationEvents);
            sort($sdkEvents);

            $isExecutionComplete = $compensationEvents === $sdkEvents
                && $offerExecution->getStatus()->equals(OfferExecutionStatusEnum::PROCESSING());

            if ($isExecutionComplete) {
                $offerExecution->setStatus(OfferExecutionStatusEnum::COMPLETE());
                $this->entityManager->persist($offerExecution);
            }

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
}