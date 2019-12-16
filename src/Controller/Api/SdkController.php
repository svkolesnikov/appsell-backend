<?php

namespace App\Controller\Api;

use App\DCI\ActionLogging;
use App\DCI\SdkEventCreating;
use App\Entity\OfferExecution;
use App\Entity\OfferLink;
use App\Exception\Api\EventExistsException;
use App\Exception\Api\EventWithBadDataException;
use App\Exception\Api\EventWithoutReferrerException;
use App\Exception\Api\FormValidationException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\ActionLogItemTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\NotFoundResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
     *  description = "Redirect to: app-<app_id>://referrer/...id",
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
        $fingerprint  = md5($request->headers->get('user-agent') . $request->server->get('REMOTE_ADDR'));
        $executionId = null;

        /** @var OfferLink $link */
        $link = $this->entityManager->find('App:OfferLink', $offerLinkId);
        if (null !== $link) {

            /** @var OfferExecution $execution */
            $execution = $this->entityManager->getRepository('App:OfferExecution')->findOneBy([
                'source_referrer_fingerprint' => $fingerprint,
                'offer_link' => $link
            ]);

            if (null !== $execution) {
                $executionId = $execution->getId();
            }
        }

        return new RedirectResponse(sprintf('app-%s://referrer/%s', $offerLinkId, $executionId));
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
     *          @SWG\Property(property = "referrer_id", type = "string", description = "ID реферальной ссылки на установку"),
     *          @SWG\Property(property = "data", type = "object", description = "Произвольные контекстные данные события в формате (key: value)")
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
     * @param SdkEventCreating $creating
     * @param ActionLogging $logging
     * @return JsonResponse
     * @throws NotFoundHttpException
     * @throws FormValidationException
     * @throws Exception
     */
    public function createEventAction(Request $request, SdkEventCreating $creating, ActionLogging $logging): JsonResponse
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
        $data['data'] = (array) $request->request->get('data', []);

        try {

            $creating->createFromSdk(
                $data['event_name'],
                $data['app_id'],
                $data['device_id'],
                $data['referrer_id'],
                $data['data'],
                $request->server->all()
            );

            return new JsonResponse(null, JsonResponse::HTTP_CREATED);

        } catch (EventExistsException $ex) {

            $logging->log(
                ActionLogItemTypeEnum::SDK_EVENT(),
                'Получено дублирующее событие от SDK (уже было записано ранее)',
                ['form' => $data],
                $request
            );

            // Если не удалось добавить событие по причине его наличия в БД
            // говорим, что все ок :)
            return new JsonResponse(null, JsonResponse::HTTP_CREATED);

        } catch (EntityNotFoundException $ex) {

            $logging->log(
                ActionLogItemTypeEnum::SDK_EVENT(),
                'Получено событие от SDK, но были переданы ID несуществующих сущностей',
                ['form' => $data],
                $request
            );

            throw new NotFoundHttpException($ex->getMessage(), $ex);

        } catch (EventWithBadDataException $ex) {

            $logging->log(
                ActionLogItemTypeEnum::SDK_EVENT(),
                "Получено событие от SDK, но были переданы некорректные данные: {$ex->getMessage()}",
                ['form' => $data],
                $request
            );

            throw new NotFoundHttpException($ex->getMessage(), $ex);

        } catch (EventWithoutReferrerException $ex) {

            $logging->log(
                ActionLogItemTypeEnum::SDK_EVENT(),
                'Получено событие от SDK без referrer_id',
                ['form' => $data],
                $request
            );

            // Если не удалось добавить событие по причине отсутствия referrer_id
            // говорим, что все ок :)
            return new JsonResponse(null, JsonResponse::HTTP_CREATED);
        }
    }
}