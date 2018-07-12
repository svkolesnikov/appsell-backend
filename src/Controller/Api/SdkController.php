<?php

namespace App\Controller\Api;

use App\DCI\SdkEventCreating;
use App\Entity\OfferExecution;
use App\Entity\OfferLink;
use App\Kernel;
use App\Lib\Controller\FormTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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

        return new RedirectResponse(sprintf('app_%s://referrer/%s', $offerLinkId, $employeeId));
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
     * @param SdkEventCreating $creating
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \App\Exception\Api\FormValidationException
     * @throws \Exception
     */
    public function createEventAction(Request $request, SdkEventCreating $creating): JsonResponse
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

        try {

            $creating->create(
                $data['event_name'],
                $data['app_id'],
                $data['device_id'],
                $data['referrer_id'],
                $request->server->all()
            );

            return new JsonResponse(null, JsonResponse::HTTP_CREATED);

        } catch (EntityNotFoundException $ex) {
            throw new NotFoundHttpException($ex->getMessage(), $ex);
        }
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