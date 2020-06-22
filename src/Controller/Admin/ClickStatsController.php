<?php

namespace App\Controller\Admin;

use App\Entity\OfferExecution;
use App\Entity\Repository\OfferExecutionRepository;
use App\Entity\User;
use App\Form\FilterClickStatsType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as SecurityCore;

class ClickStatsController extends BaseController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private SecurityCore $security;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, SecurityCore $security)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->security = $security;
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
        // Если у пользователя нет разрешения на получения отчета по
        // всем пользователям, то он видит только по тем, кто в его организации
        if (!$this->security->isGranted('ROLE_CLICK_STATS_LIST_ALL')) {
            /** @var User $user */
            $user = $this->security->getUser();
            $filter['network_email'] = $user->getEmail();
        }

        /** @var OfferExecutionRepository $repository */
        $repository = $this->em->getRepository(OfferExecution::class);
        return $repository->getClickStats($filter);
    }
}