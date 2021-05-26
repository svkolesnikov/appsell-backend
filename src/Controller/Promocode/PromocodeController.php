<?php

namespace App\Controller\Promocode;

use App\Entity\Companies;
use App\Entity\Payments;
use App\Entity\Promocode;
use App\Service\PromocodeLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PromocodeController extends AbstractController
{
    private PromocodeLogService $promocodeLog;

    public function __construct (EntityManagerInterface $entityManager)
    {
        $this->promocodeLog = new PromocodeLogService($entityManager);
    }

    /**
     * @Route("/promocode/add", name="promocode_add", methods = { "POST" })
     *
     * @return JsonResponse
     */
    public function index(Request $request): Response
    {
        $file       = $request->files->get('promo');
        $company_id = $request->headers->get('companyid');

        if (empty($file) || empty($company_id)) {
            return new JsonResponse(['message' => "Don't have any file or company ID..."], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (! $this->checkCompany($company_id)) {
            return new JsonResponse(['message' => "Company with this ID doesn't exist"], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($file->getClientOriginalExtension() === 'csv') {
            $promo = $this->getArrayFromCsv($file);
        } elseif ($file->getClientOriginalExtension() === 'xml') {
            $promo = $this->getArrayFromXml($file);
        } else {
	    return new JsonResponse(['message' => "This file format is not supported."], JsonResponse::HTTP_BAD_REQUEST);
	}

        $rep = $this->getDoctrine()->getRepository(Promocode::class);
        $rep->add($company_id, $promo);

        $this->promocodeLog->add(
            'Добавили  ' . count($promo) .' кодов компании ' . $company_id .
            ' . Список кодов - ' . json_encode($promo)
        );

        return new JsonResponse([
            'company'   => $company_id,
            'promocode' => $promo,
            'log'       => 'Добавили  ' . count($promo) .' кодов компании ' . $company_id . ' . Список кодов - ' . json_encode($promo)
        ]);
    }

    /**
     * @Route("/promocode/postback", name="promocode_postback_usage", methods = { "POST" })
     *
     * @return JsonResponse
     */
    public function postback(Request $request): Response
    {
        $promocode  = $request->headers->get('promocode');
        $company_id = $request->headers->get('companyid');

        if (! $this->checkCompany($company_id)) {
            return new JsonResponse(['message' => "Company with this ID doesn't exist"], JsonResponse::HTTP_BAD_REQUEST);
        }

        $repository = $this->getDoctrine()->getRepository(Promocode::class);
        $response   = $repository->markUsed($company_id, $promocode);

	if ($response) {
            $this->promocodeLog->add('Промокод ' . $promocode .' помечен использованным.');
        }

        return new JsonResponse(['promocode' => $promocode, 'marked used' => $response]);
    }

    
    /**
     * @Route("/promocode/checkUsage", name="promocode_checkUsage", methods = { "GET" })
     *
     * @return JsonResponse
     */
    public function checkUsage(): Response
    {
        $promocodeRepository = $this->getDoctrine()->getRepository(Promocode::class);
        $promocodes          = $promocodeRepository->findNotUsagePromocodes();

        $paymentRepository = $this->getDoctrine()->getRepository(Payments::class);

        foreach ($promocodes as $promocode) {
		$paymentRepository->setPromocodeIdNull($promocode->getId());

                $promocodeRepository->setNotUsage($promocode);

                $this->promocodeLog->add('Промокод ' . $promocode->getCode() .' помечен свободным.');
        }

        return new JsonResponse(['message' => count($promocodes) . ' has been checked']);
    }

    private function getArrayFromCsv(string $path): array
    {
        $array = fgetcsv(fopen($path, 'r'), 10000, ',');

        return array_map('trim', $array);
    }

    private function getArrayFromXml(string $path): array
    {
        $array = new \SimpleXMLElement(file_get_contents($path));

        return array_map('trim', (array) $array->code);
    }

    private function checkCompany (string $company_id): bool
    {
        return $this->getDoctrine()->getRepository(Companies::class)->existsCompany($company_id);
    }
}
