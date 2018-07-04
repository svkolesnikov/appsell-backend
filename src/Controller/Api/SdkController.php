<?php

namespace App\Controller\Api;

use App\Entity\Compensation;
use App\Entity\Offer;
use App\Entity\OfferLink;
use App\Lib\Controller\FormTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\NotFoundResponse;
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
     *      required = { "event_name", "offer_id", "offer_link_id", "device_id" },
     *      properties = {
     *          @SWG\Property(property = "event_name", type = "string"),
     *          @SWG\Property(property = "offer_id", type = "string"),
     *          @SWG\Property(property = "offer_link_id", type = "string"),
     *          @SWG\Property(property = "device_id", type = "string")
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
     * @return JsonResponse
     * @throws \App\Exception\Api\FormValidationException
     */
    public function createEventAction(Request $request): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('event_name',    Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('offer_id',      Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('offer_link_id', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('device_id',     Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        // Проверим наличие оффера, ссылки и ивента в нем

        /** @var Offer $offer */
        $offer = $this->entityManager->find('App:Offer', $data['offer_id']);
        if (null === $offer) {
            throw new NotFoundHttpException('Оффер не найден');
        }

        /** @var OfferLink $link */
        $link = $this->entityManager->getRepository('App:OfferLink')->findOneBy(['id' => $data['offer_link_id'], 'offer' => $offer]);
        if (null === $link) {
            throw new NotFoundHttpException('Приложение не найдено');
        }

        /** @var Compensation $compensation */
        $compensation = $this->entityManager->getRepository('App:Compensation')->findOneBy([
            'offer' => $offer,
            'event_type' => $data['event_name']
        ]);

        if (null === $compensation) {
            throw new NotFoundHttpException('Событие отсутствует в оффере');
        }

        // Проверим не было ли уже события от этого устройства

        $sdkEvent = $this->entityManager->getRepository('App:SdkEvent')->findOneBy([
            'offer' => $offer,
            'offer_link' => $link,
            'device_id' => $data['device_id'],
            'event_type' => $data['event_name']
        ]);

        if (null !== $sdkEvent) {

            // Если уже есть, просто скажем, что создалось успешно
            return new JsonResponse(null, JsonResponse::HTTP_CREATED);
        }

        // А вот теперь начинаем формировать событие для сохранения

    }
}