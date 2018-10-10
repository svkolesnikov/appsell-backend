<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Exception\Api\ApiException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\NotificationTypeEnum;
use App\Notification\Producer\ClientProducer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\NotFoundResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * @Route("/users")
 */
class PasswordRecoveryController
{
    use FormTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var ClientProducer */
    protected $clientProducer;

    public function __construct(EntityManagerInterface $em, ClientProducer $cp)
    {
        $this->entityManager = $em;
        $this->clientProducer = $cp;
    }

    /**
     * @SWG\Post(
     *
     *  path = "/users/password/recovery-codes",
     *  summary = "Получение кода для восстановления пароля",
     *  description = "На указанный email отправляется код для возможности изменения пароля",
     *  tags = { "Users" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Создан новый код доступа"
     *  ),
     *
     *  @BadRequestResponse(),
     *  @NotFoundResponse()
     * )
     *
     * @Route("/password/recovery-codes", methods = { "POST" })
     * @param Request $request
     * @return JsonResponse
     * @throws \App\Exception\Api\FormValidationException
     */
    public function getRecoveryCodeAction(Request $request): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('email', Type\TextType::class, ['constraints' => [new Constraints\Email(), new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        /** @var User $user */
        $user = $this->entityManager->getRepository('App:User')->findOneBy(['email' => $data['email']]);
        if (null === $user) {
            throw new NotFoundHttpException('Указанный email не зарегистрирован в системе');
        }

        if (!$user->isActive()) {
            throw new AccessDeniedHttpException('Аккаунт заблокирован');
        }

        $confirmation = $user->getConfirmation();
        $confirmation->setPasswordRecoveryCode(random_int(111111, 999999));

        $this->entityManager->persist($confirmation);
        $this->entityManager->flush();

        $this->clientProducer->produce(NotificationTypeEnum::PASSWORD_RECOVERY(), [
            'subject' => 'Код восстановления пароля на сервисе AppSell',
            'code' => $confirmation->getPasswordRecoveryCode()
        ]);

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    /**
     * @SWG\Put(
     *
     *  path = "/users/password",
     *  summary = "Изменение пароля пользователя по коду восстановления",
     *  description = "",
     *  tags = { "Users" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "new_password", "code" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "new_password", type = "string"),
     *          @SWG\Property(property = "code", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 204,
     *      description = "Пароль успешно изменен"
     *  ),
     *
     *  @BadRequestResponse(),
     *  @NotFoundResponse()
     * )
     *
     * @Route("/password", methods = { "PUT" })
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     * @throws ApiException
     * @throws \App\Exception\Api\FormValidationException
     */
    public function changePasswordAction(Request $request, UserPasswordEncoderInterface $encoder): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('email',        Type\TextType::class, ['constraints' => [new Constraints\Email(), new Constraints\NotBlank()]])
            ->add('new_password', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('code',         Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        /** @var User $user */
        $user = $this->entityManager->getRepository('App:User')->findOneBy(['email' => $data['email']]);
        if (null === $user) {
            throw new NotFoundHttpException('Указанный email не зарегистрирован в системе');
        }

        $confirmation = $user->getConfirmation();
        if ($data['code'] !== $confirmation->getPasswordRecoveryCode()) {
            throw new ApiException('Неверный код восстановления пароля');
        }

        $user->setPassword($encoder->encodePassword($user, $data['new_password']));
        $confirmation->setPasswordRecoveryCode(null);
        $confirmation->setPasswordRecoveryTime(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}