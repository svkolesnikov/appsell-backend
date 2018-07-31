<?php

namespace App\Service;

use App\DataSource\OwnerOfferDataSource;
use App\DataSource\SellerOfferDataSource;
use App\Entity\Report;
use App\Entity\User;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;

class ReportService
{
    /** @var UserGroupManager */
    protected $userGroupManager;

    /** @var OwnerOfferDataSource */
    protected $ownerDataSource;

    /** @var SellerOfferDataSource */
    protected $sellerDataSource;

    /** @var EntityManagerInterface */
    protected $em;

    public function __construct(
        UserGroupManager $userGroupManager,
        OwnerOfferDataSource $ods,
        SellerOfferDataSource $sds,
        EntityManagerInterface $em
    )
    {
        $this->userGroupManager = $userGroupManager;
        $this->ownerDataSource = $ods;
        $this->sellerDataSource = $sds;
        $this->em = $em;
    }

    /**
     *
     * Формирование финансового отчета для пользователя
     *
     * @param User $user
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @throws \App\Exception\Api\DataSourceException
     * @throws \Exception
     */
    public function create(User $user, \DateTime $startDate, \DateTime $endDate): void
    {
        if ($this->userGroupManager->hasGroup($user, UserGroupEnum::OWNER())) {
            $data = $this->ownerDataSource->getFinanceReport($user, $startDate, $endDate);

        } elseif ($this->userGroupManager->hasGroup($user, UserGroupEnum::SELLER())) {
            $data = $this->sellerDataSource->getFinanceReport($user, $startDate, $endDate);

        } else {
            throw new \Exception('Финансовые отчеты предусмотрены только для заказчиков и продавцов!');
        }

        $report = new Report();
        $report->setUser($user);
        $report->setStartDate($startDate);
        $report->setEndDate($endDate);
        $report->setData(json_encode($data));

        $this->em->persist($report);
        $this->em->flush();
    }

    public function prepareCsvReport(Report $report)
    {
        $rows   = [
            sprintf('Период формирования отчета: %s - %s%s',
                $report->getStartDate()->format('d.m.Y'),
                $report->getEndDate()->format('d.m.Y'),
                PHP_EOL
            ),
            ';' . PHP_EOL,
            ';' . PHP_EOL,
            'Наименование оффера;Количество исполнений;Сумма;НДС;' . PHP_EOL
        ];

        $data = json_decode($report->getData(), true);

        foreach ($data as $item) {
            $rows[] = sprintf('%s;%s;="%s";="%s";%s',
                $item['title'], $item['count'], $item['sum'], $item['tax'], PHP_EOL
            );
        }

        $result = '';
        foreach ($rows as $row) {
            $result .= mb_convert_encoding($row, 'Windows-1251', 'UTF-8');
        }

        return $result;
    }
}