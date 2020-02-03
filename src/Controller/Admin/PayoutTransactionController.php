<?php

namespace App\Controller\Admin;

use App\Entity\OfferExecution;
use App\Entity\PayoutTransaction;
use App\Entity\Repository\OfferExecutionRepository;
use App\Entity\Repository\PayoutTransactionRepository;
use App\Entity\User;
use App\Exception\Api\SolarStaffException;
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
     */
    public function listAction(Request $request)
    {
        $page    = $request->get('_page', 1);
        $perPage = $request->get('_per_page', 16);
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

    /**
     * @Route("/admin/payout-transations", methods={"POST"}, name="app_payout_transation_approve")
     * @Security("is_granted('ROLE_PAYOUT_APPROVE')")
     *
     * @param Request $request
     * @param Client $client
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @return RedirectResponse
     */
    public function payoutAction(
        Request $request,
        Client $client,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    )
    {
        /** @var PayoutTransaction $payoutTransaction */
        $payoutTransaction = $em->getRepository(PayoutTransaction::class)->find($request->request->get('transaction_id'));

        /** @var User $employee */
        $employee = $payoutTransaction->getReceiver();

        $isSolarStaffProcessable = $payoutTransaction->getDestination()->equals(PayoutDestinationEnum::SOLAR_STAFF())
            && $employee->getProfile()->isSolarStaffConnected();

        // Тут будем обрабатывать только транзакции SolarStaff
        if ($isSolarStaffProcessable) {

            /** @var OfferExecutionRepository $executionRepository */
            $executionRepository = $em->getRepository(OfferExecution::class);

            /** @var OfferExecution[] $executions */
            $executions = $executionRepository->findBy(['payout_transaction' => $payoutTransaction]);

            // Сформируем список выполненных приложений, для отчетночти
            // в solar staff

            $attributes = [];
            $offers     = [];

            foreach ($executions as $e) {

                $offerTitle = $e->getOffer()->getTitle();
                if (!isset($offers[$offerTitle])) {
                    $offers[$offerTitle] = 0;
                }

                $offers[$offerTitle]++;
            }

            // Должно получиться что-то вроде:
            //
            // Яндекс еда - 7;
            // Телеграмм - 52;
            // eBox - 21

            foreach ($offers as $title => $count) {
                $attributes[] = sprintf('%s – %d', $title, $count);
            }

            // Поехали выводить бабло

            try {

                $response = $client->payout(
                    $employee->getProfile()->getSolarStaffId(),
                    $payoutTransaction->getId(),
                    $payoutTransaction->getAmount(),
                    $attributes
                );

            } catch (SolarStaffException $ex) {
                $response['error_text'] = $ex->getMessage();
            }

            $transactionInfo = $payoutTransaction->getInfo();
            $transactionInfo['response'] = $response;
            $payoutTransaction->setInfo($transactionInfo);

            $em->persist($payoutTransaction);
            $em->flush();
        }

        // Отправим челика обратно в список
        $backUrl = $request->request->get('back_url', '/admin/payout-transations');
        return RedirectResponse::create($backUrl);
    }
}