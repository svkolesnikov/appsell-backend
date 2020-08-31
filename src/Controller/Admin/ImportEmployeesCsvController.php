<?php

namespace App\Controller\Admin;

use App\DCI\ImportEmployeesFromCsv;
use App\Form\EmployeesCsvImportType;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class ImportEmployeesCsvController extends BaseController
{
    private LoggerInterface $logger;
    private RouterInterface $router;
    private ImportEmployeesFromCsv $importer;

    public function __construct(LoggerInterface $logger, RouterInterface $router, ImportEmployeesFromCsv $importer)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->importer = $importer;
    }

    /**
     * @Route("/admin/import/employees-csv", methods={"GET", "POST"}, name="app_import_employees_csv")
     * @Security("is_granted('ROLE_IMPORT_EMPLOYEES_CSV')")
     *
     * @param Request $request
     * @return Response
     */
    public function startAction(Request $request)
    {
        $form = $this->createForm(EmployeesCsvImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $this->importer->import($data['employeer_id'], $data['file']);

            // Переход к списку пользователей выбранного работодателя
            $resultUrl = $this->router->generate('app_settings_users_list', ['filter' => ['seller' => $data['employeer_id']]]);
            return new RedirectResponse($resultUrl);
        }

        return $this->render('pages/import_employees_csv/form.html.twig', [
            'form' => $form->createView()
        ]);
    }
}