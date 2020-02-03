<?php

namespace App\Controller\Admin;

use App\Entity\PayoutTransaction;
use App\Entity\Repository\PayoutTransactionRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PayoutTransactionController extends BaseController
{
    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * @Route("/admin/payout-transations", methods={"GET"}, name="app_payout_transation_list")
     * @Security("is_granted('ROLE_PAYOUT_LIST')")
     *
     * @param Request $request
     * @return Response
     * @throws DBALException
     */
    public function listAction(Request $request)
    {
        $page    = $request->get('_page', 1);
        $perPage = $request->get('_per_page', 32);
        $offset  = ($page - 1) * $perPage;

        /** @var PayoutTransactionRepository $repository */
        $repository = $this->em->getRepository(PayoutTransaction::class);
        $items = $repository->findBy([], ['id' => 'desc'], $perPage, $offset);

        return $this->render('pages/payout_transaction/list.html.twig', [
            'items' => $items,
            'pager'            => [
                '_per_page'    => $perPage,
                '_page'        => $page,
                '_has_more'    => \count($items) >= $perPage
            ]
        ]);
    }
}