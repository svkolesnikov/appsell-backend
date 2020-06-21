<?php

namespace App\Controller\Admin;

use App\Entity\OfferExecution;
use App\Entity\PayoutTransaction;
use App\Entity\Repository\OfferExecutionRepository;
use App\Entity\Repository\PayoutTransactionRepository;
use App\Entity\User;
use App\Exception\Api\SolarStaffException;
use App\Form\FilterClickStatsType;
use App\Lib\Enum\PayoutDestinationEnum;
use App\SolarStaff\Client;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ClickStatsController extends BaseController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $em;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * @Route("/admin/clicks-statistics", methods={"GET"}, name="app_click_stats_list")
     * @Security("is_granted('ROLE_CLICK_STATS_LIST')")
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(FilterClickStatsType::class);
        $form->handleRequest($request);
        $items = $this->getItems($form->getData() ?? []);
        $downloadUrl = '/admin/clicks-statistics/csv?' . http_build_query($request->query->all());

        return $this->render('pages/click_stats/list.html.twig', [
            'form'  => $form->createView(),
            'items' => $items,
            'csv_link' => $downloadUrl
        ]);
    }

    /**
     * @Route("/admin/clicks-statistics/csv", methods={"GET"}, name="app_click_stats_csv_download")
     * @Security("is_granted('ROLE_CLICK_STATS_LIST')")
     *
     * @param Request $request
     * @return Response
     */
    public function downloadCsvAction(Request $request)
    {
        $form = $this->createForm(FilterClickStatsType::class);
        $form->handleRequest($request);
        $items = $this->getItems($form->getData() ?? []);

        if (empty($items)) {
            return new RedirectResponse('/admin/clicks-statistics');
        }

        $csvData = [
            implode(',', [
                'click_time',
                'event_time',
                'parent_email',
                'seller_email',
                'network_name',
                'click_status',
                'event_title',
                'event_name',
                'offer_id',
                'offer_name',
                'click_id',
                'sum_fee',
                'event_source',
            ])
        ];

        foreach ($items as $row) {
            $csvData[] = implode(',', array_values($row));
        }

        $response = implode(PHP_EOL, $csvData);
        return new Response(
            $response,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="report.csv"',
                'Content-Length' => strlen($response)
            ]
        );
    }

    private function getItems(array $filter): array
    {
        /** @var OfferExecutionRepository $repository */
        $repository = $this->em->getRepository(OfferExecution::class);
        return $repository->getClickStats($filter);
    }
}