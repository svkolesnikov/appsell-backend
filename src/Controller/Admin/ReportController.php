<?php

namespace App\Controller\Admin;

use App\Entity\Report;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends BaseController
{
    /** @var  EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin/reports", name="app_report_list")
     *
     * @Security("has_role('ROLE_APP_REPORT_LIST')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \UnexpectedValueException
     */
    public function listAction(Request $request): Response
    {
        $page     = $request->get('_page', 1);
        $perPage  = $request->get('_per_page', 16);
        $offset   = ($page-1) * $perPage;
        $items    = [];
        $criteria = [];

        if ( ! $this->isGranted('ROLE_SUPER_ADMIN')) {
            $criteria['user'] = $this->getUser();
        }

        try {
            $items = $this->em->getRepository(Report::class)->findBy($criteria, ['ctime' => 'DESC'], $perPage, $offset);
        } catch (\Exception $ex) {
            $this->addFlash('error', 'Не удалось получить список отчетов. ' . $ex->getMessage());
        }

        return $this->render('pages/report/list.html.twig', [
            'items' => $items,
            'pager' => [
                '_per_page' => $perPage,
                '_page'     => $page,
                '_has_more' => \count($items) >= $perPage
            ]
        ]);
    }

    /**
     * @Route("/admin/reports/{id}/download", name="app_report_download")
     *
     * @Security("has_role('ROLE_APP_REPORT_LIST')")
     *
     * @param Request $request
     *
     * @param Report $report
     * @return Response
     */
    public function downloadAction(Request $request, Report $report, ReportService $reportService): Response
    {
        $filename = sprintf('report_%s.csv', uniqid());
        $path     = sprintf('%s/%s', sys_get_temp_dir(), $filename);

        file_put_contents($path, $reportService->prepareCsvReport($report));

        return $this->file($path, $filename);
    }
}