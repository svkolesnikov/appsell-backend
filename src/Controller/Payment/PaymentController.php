<?php

namespace App\Controller\Payment;

use App\Entity\Companies;
use App\Entity\Payments;
use App\Entity\Promocode;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\Offer;
use chillerlan\QRCode\QRCode;
use App\Entity\OrderNumbers;
use App\Entity\Members;
use App\Service\PaymentsLogService;
use App\Service\PromocodeService;
use App\Service\RquidService;
use App\Service\SberbankService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    protected EntityManagerInterface $em;
    protected SberbankService $sberbank;
    private PromocodeService $promocode;
    private PaymentsLogService $log;

    public function __construct (EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->em        = $entityManager;
        $this->sberbank  = new SberbankService($entityManager, $parameterBag);
        $this->promocode = new PromocodeService($entityManager);
        $this->log       = new PaymentsLogService($entityManager);
    }

    /**
     * @Route("/payments/create", name="payments_payment", methods = { "POST" })
     *
     * @return JsonResponse
     */
    public function createPayment (Request $request): Response
    {
        $seller_id = htmlspecialchars($request->headers->get('sellerid'));
        $offer_id  = htmlspecialchars($request->headers->get('offerid'));

        $offer = $this->getDoctrine()->getRepository(Offer::class);
        if (! $offer->check($offer_id) || ! $offer->checkPayQr($offer_id)) {
            return new JsonResponse(
                ['message' => "Offer not found or don't supported Pay QR", 'data' => $offer_id],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $seller = $this->getDoctrine()->getRepository(UserProfile::class);
        if (! $seller->check($seller_id) || ! $seller->checkIdQr($seller_id)) {
            return new JsonResponse(
                ['message' => "Seller doesn't exist or doesn't have a ID QR", 'data' => $seller_id],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $newOrder = $this->getCreateData($request, $this->em);
        if (count($newOrder) !== 13) {
            return new JsonResponse(['message' => 'Not enough data...', 'data' => $newOrder], JsonResponse::HTTP_BAD_REQUEST);
        }

        $order_number             = $this->em->getRepository(OrderNumbers::class)->getNewNumber();
        $newOrder['order_number'] = $order_number->getId();

        $members               = $this->em->getRepository(Members::class)->getNewMember();
        $newOrder['member_id'] = $members->getId();

        if (! $this->sellerExists($newOrder['seller_id'])) {
            return new JsonResponse(['message' => "Seller doesn't exist", 'data' => $newOrder], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (! $this->checkCompany($newOrder['company_id'])) {
            return new JsonResponse(['message' => "Company with this ID doesn't exist", 'data' => $newOrder], JsonResponse::HTTP_BAD_REQUEST);
        }


        $repository = $this->em->getRepository(Payments::class);
        $payment    = $repository->createNewPayment($newOrder);
        $this->log->add('Платеж ' . $payment->getid() .' создан.');

        $sber = $this->sberbank->createPayment($newOrder);

        if ($sber[0] != 200 || ! isset($sber[1]->status->order_form_url)) {
            return new JsonResponse(
                [
                    'message'  => 'Payment not been created!',
                    'data'     => $newOrder,
                    'response' => $sber[1],
                ],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $order_number->setPaymentId($payment->getId());
        $members->setPaymentId($payment->getId());

        $payment = $repository->addSberbankResponse($payment, $sber[1]);
        $this->log->add('QR-код для платежа ' . $payment->getid() .' создан.');

        return new JsonResponse(
            [
                'qr_url'   => $sber[1]->status->order_form_url,
//                'qr_code'  => (new QRCode())->render($sber[1]->status->order_form_url),
                'order_id' => $sber[1]->status->order_id
            ]
        );
    }

    /**
     * @Route("/payments/status", name="payments_status", methods = { "GET" })
     *
     * @return JsonResponse
     */
    public function statusPayment(): Response
    {
	    $repository = $this->getDoctrine()->getRepository(Payments::class);
        $payments   = $repository->findPaymentInProcess();

        foreach ($payments as $payment) {

            $status = $this->sberbank->statusPayment($payment);

            if ($status[0] == 200 && $status[1]->status->order_state === 'PAID') {
                $promocode = $this->promocode->get($payment->getSellerId(), $payment->getCompanyId());
                $repository->markPaid($payment);

                if ($promocode) {
                    $repository->addPromocodeToPayment($payment, $promocode->getId());
                }

                $this->log->add('Оплата по платежу ' . $payment->getid() .' подтверждена.');

                $response[] = [$payment->getRquid() => 'true'];
            }
        }

        return new JsonResponse($response ??  ['message' => 'Nothing to check']);
    }

    /**
     * @Route("/payments/checkPayment", name="payments_check", methods = { "POST" })
     *
     * @return JsonResponse
     */
    public function checkPayment(Request $request): Response
    {
        $order = $this->getCheckData($request);
        if (count($order) !== 4)
            return new JsonResponse(['message' => 'Not enough data...', 'data' => $order], JsonResponse::HTTP_BAD_REQUEST);

        $repository   = $this->getDoctrine()->getRepository(Payments::class);
        $payment      = $repository->checkPayment($order['seller_id'], $order['order_id']);

        if (! $payment)
            return new JsonResponse(['message' => 'Payment ' . $order['order_id'] . ' not found.']);

        if ($payment->getStatus() == 0)
            return new JsonResponse(['message' => 'Payment ' . $order['order_id'] . ' not been paid yet.']);

        if ($payment->getStatus() == 1) {
	    if ($payment->getPromocodeId() != null) {
                $repository = $this->getDoctrine()->getRepository(Promocode::class);
                $promocode  = $repository->find($payment->getPromocodeId());

		$code = $promocode->getCode();
            }
        }

        if ($payment->getStatus() == 2)
            return new JsonResponse(['message' => 'Payment ' . $order['order_id'] . ' has been revoked.']);

        return new JsonResponse([
            'message'   => 'Order ' . $order['order_id'] . ' successfully paid.',
            'promocode' => $code ?? 'Not provided'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * @Route("/payments/revoke", name="payments_revoke", methods = { "POST" })
     *
     * @return JsonResponse
     */
    public function revokePayment(Request $request): Response
    {
        $order = $this->getCheckData($request);
        if (count($order) !== 4)
            return new JsonResponse(['message' => 'Not enough data...', 'data' => $order], JsonResponse::HTTP_BAD_REQUEST);

        $repository = $this->getDoctrine()->getRepository(Payments::class);
        $payment    = $repository->findOneForRevoke($order['seller_id'], $order['order_id']);

	    if (! $payment)
	        return new JsonResponse(['message' => 'Payment ' . $order['order_id'] . ' not found.']);

        //$revoke = $this->sberbank->revokePayment($payment);
        $repository->markRevoked($payment);

        $this->log->add('Платеж ' . $payment->getId() .' отменен клиентом.');

        return new JsonResponse(['message' => 'Payment ' . $order['order_id'] . ' has been revoked.']);
    }

    /**
     * @Route("/payments/autoRevoke", name="payments_autoRevoke", methods = { "GET" })
     *
     * @return JsonResponse
     */
    public function autoRevokePayment(Request $request): Response
    {
        $repository = $this->getDoctrine()->getRepository(Payments::class);
        $payments   = $repository->findAllNotPaidYet();

        foreach ($payments as $payment) {
        	$repository->markRevoked($payment);

                $this->log->add('Платеж ' . $payment->getId() .' отменен по истечению времени.');
        }

        return new JsonResponse(['message' => 'Checked ' . count($payments) . ' payment(s). ']);
    }

    private function getCheckData(Request $request): array
    {
        $order['order_id']  = htmlspecialchars($request->headers->get('orderid'));
        $order['seller_id'] = htmlspecialchars($request->headers->get('sellerid'));
        $order['rq_uid']    = $this->getRquid();
        $order['rq_tm']     = gmdate('Y-m-d\TH:i:s') . 'Z';

        return $this->checkData($order);
    }

    private function getCreateData(Request $request, EntityManagerInterface $em): array
    {
        $order['seller_id'] = htmlspecialchars($request->headers->get('sellerid'));
        $offer_id           = htmlspecialchars($request->headers->get('offerid'));

        $offer  = $this->getDoctrine()->getRepository(Offer::class)->find($offer_id);
        $seller = $this->getDoctrine()->getRepository(UserProfile::class)->find($order['seller_id']);

        $order['rq_uid']               = $this->getRquid();
        $order['rq_tm']                = gmdate('Y-m-d\TH:i:s') . 'Z';
        $order['id_qr']                = $seller->getIdQr();
        $order['company_id']           = $seller->getCompanyId();
        $order['currency']             = "810"; //Russian RUB
        $order['position_count']       = 1;
        $order['position_sum']         = substr($offer->getPrice(), 0, 15);
        $order['position_name']        = substr(htmlspecialchars($offer->getTitle()), 0, 256);
        $order['position_description'] = substr(htmlspecialchars($offer->getDescription()), 0, 1024);
        $order['order_description']    = substr(htmlspecialchars($offer->getDescription()), 0, 256);
        $order['order_sum']            = $this->getOrderSum($order, $request);
        $order['order_create_date']    = $order['rq_tm'];

        return $this->checkData($order);
    }

    private function checkData (array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === '' || $value === null) {
                $response[] = [
                    'message' => $key . ' is empty or null'
                ];
            }
        }

        return $response ?? $data;
    }

    private function getOrderSum (array $order, Request $request): string
    {
        $sum 	    = $request->request->get('order_sum');
	$checkedSum = bcmul($order['position_count'], $order['position_sum']);

        return ($sum === $checkedSum) ? $sum : $checkedSum;
    }

    private function getRquid(): string
    {
        return (new RquidService($this->em))->get();
    }

    private function sellerExists (string $seller): bool
    {
        $repository = $this->getDoctrine()->getRepository(User::class);

        return $repository->checkSeller($seller);
    }

    private function checkCompany (string $company_id): bool
    {
        return $this->getDoctrine()->getRepository(Companies::class)->existsCompany($company_id);
    }
}
