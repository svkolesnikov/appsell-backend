<?php

namespace App\Controller\Admin;

use App\DCI\ImportEventsFromCsv;
use App\Form\EventsCsvImportType;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImportEventsCsvController extends BaseController
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/admin/import/events-csv", methods={"GET"}, name="app_import_csv_list")
     * @Security("has_role('ROLE_IMPORT_CSV_LIST')")
     *
     * @param Request $request
     * @return Response
     */
    public function startAction(Request $request)
    {
        $form = $this->createForm(EventsCsvImportType::class);

        return $this->render('pages/import_event_csv/list.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/import/events-csv/import", methods={"POST"}, name="app_import_csv_import")
     * @Security("has_role('ROLE_IMPORT_CSV_IMPORT')")
     *
     * @param Request $request
     * @param ImportEventsFromCsv $importer
     * @return Response
     */
    public function importAction(Request $request, ImportEventsFromCsv $importer)
    {
        $form = $this->createForm(EventsCsvImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {

                $data = $form->getData();
                $importer->import(
                    $data['delimeter'],
                    $data['click_id_column'],
                    $data['event_column'],
                    $data['file'],
                    $this->getUser()->getId()
                );

                return new RedirectResponse('/admin/import/events-csv');

            } catch (\Exception $ex) {

                $this->logger->error('Проблема при импорте событий из CSV файла: ' . $ex->getMessage());
                $form->addError(new FormError($ex->getMessage()));
            }
        }

        return $this->render('pages/import_event_csv/list.html.twig', [
            'form' => $form->createView(),
            'list' => []
        ]);
    }
}