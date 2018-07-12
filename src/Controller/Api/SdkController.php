<?php

namespace App\Controller\Api;

use App\Entity\Compensation;
use App\Entity\EventType;
use App\Entity\Offer;
use App\Entity\OfferExecution;
use App\Entity\OfferLink;
use App\Entity\SdkEvent;
use App\Entity\User;
use App\Kernel;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\CurrencyEnum;
use App\Lib\Enum\SdkEventSourceEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\NotFoundResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * @Route("/sdk")
 */
class SdkController
{
    use FormTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @SWG\Get(
     *
     *  path = "/sdk/deep-link",
     *  summary = "Переход в приложение по deferred deep link",
     *  description = "Redirect to: app_<app_id>://referrer/...id",
     *  tags = { "SDK" },
     *
     *  @SWG\Parameter(name = "app_id", required = true, in = "query", type = "string"),
     *
     *  @SWG\Response(
     *      response = 302,
     *      description = "Переход в приложение"
     *  ),
     *
     *  @BadRequestResponse(),
     *  @NotFoundResponse()
     * )
     *
     * @Route(methods = {"GET"}, path = "/deep-link")
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws \InvalidArgumentException
     */
    public function followDeepLinkAction(Request $request): RedirectResponse
    {
        $offerLinkId = $request->get('app_id');
        $fingerprint = md5($request->headers->get('user-agent') . $request->server->get('REMOTE_ADDR'));
        $employeeId  = null;

        /** @var OfferLink $link */
        $link = $this->entityManager->find('App:OfferLink', $offerLinkId);
        if (null !== $link) {

            /** @var OfferExecution $execution */
            $execution = $this->entityManager->getRepository('App:OfferExecution')->findOneBy([
                'source_referrer_fingerprint' => $fingerprint,
                'offer_link' => $link
            ]);

            if (null !== $execution) {
                $employeeId = $execution->getSourceLink()->getUser()->getId();
            }
        }

        return new RedirectResponse(sprintf('app%s://referrer/%s', $offerLinkId, $employeeId));
    }

    /**
     * @SWG\Post(
     *
     *  path = "/sdk/events",
     *  summary = "Получение событий от SDK",
     *  description = "",
     *  tags = { "SDK" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "event_name", "offer_link_id", "device_id" },
     *      properties = {
     *          @SWG\Property(property = "event_name", type = "string"),
     *          @SWG\Property(property = "app_id", type = "string", description = "Зашивается в приложении"),
     *          @SWG\Property(property = "device_id", type = "string", description = "Уникальный идентификатор устройства"),
     *          @SWG\Property(property = "referrer_id", type = "string", description = "ID пользователя, передавшего реферальную ссылку на установку")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Событие зафиксировано"
     *  ),
     *
     *  @BadRequestResponse(),
     *  @NotFoundResponse()
     * )
     *
     * @Route("/events", methods = { "POST" })
     * @param Request $request
     * @param UserGroupManager $gm
     * @return JsonResponse
     * @throws \App\Exception\Api\FormValidationException
     */
    public function createEventAction(Request $request, UserGroupManager $gm): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('event_name',  Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('app_id',      Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('device_id',   Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('referrer_id', Type\TextType::class)
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        // Проверим наличие оффера, ссылки и ивента в нем

        /** @var OfferLink $link */
        $link = $this->entityManager->find('App:OfferLink', $data['app_id']);
        if (null === $link) {
            throw new NotFoundHttpException('Приложение не найдено');
        }

        /** @var Compensation $compensation */
        $compensation = $this->entityManager->getRepository('App:Compensation')->findOneBy([
            'offer'      => $link->getOffer(),
            'event_type' => $data['event_name']
        ]);

        if (null === $compensation) {
            throw new NotFoundHttpException('Событие отсутствует в оффере');
        }

        // Проверим наличие сотрудника
        // Если не нашли сотрудника по ID, то событие запишется в БД
        // без ссылки на пользователя, с 0 суммами, и не будет влиять
        // на выплаты и статистику

        /** @var User $employee */
        $employee = $data['referrer_id']
            ? $this->entityManager->find('App:User', $data['referrer_id'])
            : null;

        // Вытащим еще и событие
        // Проверять, получили ли мы сам ивент смысла нет, тк до этого мы получили с
        // его участием компенсацию, а мы бы этого не смогли, если бы ивент не существовал
        // это гарантируют ключи в БД

        /** @var EventType $eventType */
        $eventType = $this->entityManager->find('App:EventType', $data['event_name']);

        // Проверим не было ли уже события от этого устройства

        $sdkEvent = $this->entityManager->getRepository('App:SdkEvent')->findOneBy([
            'offer'      => $link->getOffer(),
            'offer_link' => $link,
            'device_id'  => $data['device_id'],
            'event_type' => $eventType
        ]);

        if (null !== $sdkEvent) {

            // Если уже есть, просто скажем, что создалось успешно
            return new JsonResponse(null, JsonResponse::HTTP_CREATED);
        }

        // Получим ссылку на OfferExecution
        // если нет ни одного "свободного", то будем его создавать
        // при этом, если не был передан referrer_id, то можно создать
        // формальный OfferExecution, без привязки к реферальной ссылке
        // а суммы в event записать нулевые



        // А вот теперь начинаем формировать событие для сохранения

        $newEvent = new SdkEvent();
        $newEvent->setEventType($eventType);
//        $newEvent->setOfferExecution()
        $newEvent->setDeviceId($data['device_id']);
        $newEvent->setSourceInfo($request->server->all());
        $newEvent->setCurrency(CurrencyEnum::RUB());
//        $newEvent->setAmountForService()
//        $newEvent->setAmountForSeller()
//        $newEvent->setAmountForEmployee()
        $newEvent->setOffer($link->getOffer());
        $newEvent->setOfferLink($link);
        $newEvent->setSource(SdkEventSourceEnum::APP());
        $newEvent->setEmployee($employee);
    }

    /**
     * @Route("/tmp2", methods = { "POST" })
     * @param Request $request
     * @param ContainerInterface $container
     * @return JsonResponse
     */
    public function test2Action(Request $request, ContainerInterface $container): JsonResponse
    {
        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $path   = $kernel->getProjectDir() . '/var/tmp2params.log';

        file_put_contents(
            $path,
            date('Y-m-d H:i:s') . PHP_EOL . print_r($request->request->all(), true) . PHP_EOL,
            FILE_APPEND
        );

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }
}