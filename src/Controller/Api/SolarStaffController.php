<?php

namespace App\Controller\Api;

use App\SolarStaff\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\SolarStaffInfoSchema;

class SolarStaffController
{
    /** @var Client */
    protected $client;

    public function __construct(Client $ssc)
    {
        $this->client = $ssc;
    }

    /**
     * @SWG\Get(
     *
     *  path = "/solar-staff",
     *  summary = "Информация о Solar Staff",
     *  description = "",
     *  tags = { "Info", "Solar-Staff" },
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "",
     *      @SolarStaffInfoSchema()
     *  )
     * )
     *
     * @Route("/solar-staff", methods = { "GET" })
     */
    public function indexAction(): JsonResponse
    {
        return new JsonResponse([
            'oferta_url' => $this->client->getOfertaUrl(),
            'login_url'  => $this->client->getLoginUrl()
        ]);
    }
}