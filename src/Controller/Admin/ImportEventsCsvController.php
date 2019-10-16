<?php

namespace App\Controller\Admin;

use App\Form\EventsCsvImportType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImportEventsCsvController extends BaseController
{
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
     */
    public function importAction(Request $request)
    {
        $form = $this->createForm(EventsCsvImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            echo '<pre>';
            var_dump($form->getData());
            die();
        }
    }
}