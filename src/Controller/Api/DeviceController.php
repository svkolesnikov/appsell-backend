<?php

namespace App\Controller\Api;

use App\Entity\DevicePushToken;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\DeviceTokenTypeEnum;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\DeviceTokenSchema;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints;

/**
 * @Route("/users")
 */
class DeviceController
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
     *  path = "/users/current/devices/push-tokens",
     *  summary = "Добавление нового токена для push-уведомлений",
     *  description = "",
     *  tags = { "Users" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body", @DeviceTokenSchema()),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Токен для push-уведомлений добавлен"
     *  ),
     *
     *  @BadRequestResponse(),
     *  @AccessDeniedResponse(),
     *  @UnauthorizedResponse()
     * )
     *
     * @Route("/current/devices/push-tokens", methods = { "POST" })
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @return JsonResponse
     * @throws \App\Exception\Api\FormValidationException
     */
    public function createPushTokenAction(Request $request, TokenStorageInterface $tokenStorage): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('type',  Type\ChoiceType::class, ['constraints' => [new Constraints\NotBlank()], 'choices' => DeviceTokenTypeEnum::toArray()])
            ->add('token', Type\TextType::class,   ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        try {

            $this->entityManager->beginTransaction();

            $pushToken = new DevicePushToken();
            $pushToken->setUser($tokenStorage->getToken()->getUser());
            $pushToken->setType(new DeviceTokenTypeEnum($data['type']));
            $pushToken->setToken($data['token']);

            $this->entityManager->persist($pushToken);
            $this->entityManager->flush();

            $this->entityManager->commit();

        } catch (UniqueConstraintViolationException $ex) {

            // Если токен уже есть, просто отменим транзакцию
            // и скажем что все успешно добавилось
            $this->entityManager->rollback();
        }

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }
}