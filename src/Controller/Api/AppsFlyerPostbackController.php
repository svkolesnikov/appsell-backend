<?php

namespace App\Controller\Api;

use App\DCI\ActionLogging;
use App\DCI\SdkEventCreating;
use App\Exception\Api\EventWithoutReferrerException;
use App\Exception\Api\FormValidationException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\ActionLogItemTypeEnum;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * @Route("/appsflyer")
 */
class AppsFlyerPostbackController
{
    use FormTrait;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route(methods = {"GET"}, path = "/postbacks/install")
     * @param Request $request
     * @param SdkEventCreating $creating
     * @param ActionLogging $logging
     * @return JsonResponse
     * @throws FormValidationException
     */
    public function installPostbackAction(Request $request, SdkEventCreating $creating, ActionLogging $logging)
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('clickid', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);

        $data = $form->getData();
        $data['data'] = (array) $request->request->get('data', []);

        try {

            $creating->createFromClickId($data['clickid'], 'installation', $request->server->all());
            return new JsonResponse(null, JsonResponse::HTTP_CREATED);

        } catch (UniqueConstraintViolationException $ex) {

            $logging->log(
                ActionLogItemTypeEnum::APPSFLYER_EVENT(),
                'Получено дублирующее событие от appsflyer (уже было записано ранее)',
                ['form' => $data],
                $request
            );

            // Если не удалось добавить событие по причине его наличия в БД
            // говорим, что все ок :)
            return new JsonResponse(null, JsonResponse::HTTP_CREATED);

        } catch (EntityNotFoundException $ex) {

            $logging->log(
                ActionLogItemTypeEnum::APPSFLYER_EVENT(),
                'Получено событие от appsflyer, но были переданы ID несуществующих сущностей',
                ['form' => $data],
                $request
            );

            return new JsonResponse(null, JsonResponse::HTTP_CREATED);

        } catch (EventWithoutReferrerException $ex) {

            $logging->log(
                ActionLogItemTypeEnum::SDK_EVENT(),
                'Получено событие от appsflyer с неверным click_id',
                ['form' => $data],
                $request
            );

            // Если не удалось добавить событие по причине отсутствия click_id
            // говорим, что все ок :)
            return new JsonResponse(null, JsonResponse::HTTP_CREATED);
        }
    }

    /**
     * @Route(methods = {"GET"}, path = "/postbacks/in-app-event")
     * @param Request $request
     * @param SdkEventCreating $creating
     * @param ActionLogging $logging
     * @return JsonResponse
     * @throws FormValidationException
     */
    public function inAppEventPostbackAction(Request $request, SdkEventCreating $creating, ActionLogging $logging)
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('clickid', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('partner_event_name', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);

        $data = $form->getData();
        $data['data'] = (array) $request->request->get('data', []);

        try {

            $creating->createFromClickId($data['clickid'], $data['partner_event_name'], $request->server->all());
            return new JsonResponse(null, JsonResponse::HTTP_CREATED);

        } catch (UniqueConstraintViolationException $ex) {

            $logging->log(
                ActionLogItemTypeEnum::APPSFLYER_EVENT(),
                'Получено дублирующее событие от appsflyer (уже было записано ранее)',
                ['form' => $data],
                $request
            );

            // Если не удалось добавить событие по причине его наличия в БД
            // говорим, что все ок :)
            return new JsonResponse(null, JsonResponse::HTTP_CREATED);

        } catch (EntityNotFoundException $ex) {

            $logging->log(
                ActionLogItemTypeEnum::APPSFLYER_EVENT(),
                'Получено событие от appsflyer, но были переданы ID несуществующих сущностей',
                ['form' => $data],
                $request
            );

            return new JsonResponse(null, JsonResponse::HTTP_CREATED);

        } catch (EventWithoutReferrerException $ex) {

            $logging->log(
                ActionLogItemTypeEnum::SDK_EVENT(),
                'Получено событие от appsflyer с неверным click_id',
                ['form' => $data],
                $request
            );

            // Если не удалось добавить событие по причине отсутствия click_id
            // говорим, что все ок :)
            return new JsonResponse(null, JsonResponse::HTTP_CREATED);
        }
    }
}