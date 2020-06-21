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

        return $this->render('pages/click_stats/list.html.twig', [
            'form'  => $form->createView(),
            'items' => $items,
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

    }

    private function getItems(array $filter): array
    {
        /** @var OfferExecutionRepository $repository */
        $repository = $this->em->getRepository(OfferExecution::class);
        return $repository->getClickStats($filter);
    }
}