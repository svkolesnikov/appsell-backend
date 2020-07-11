<?php

namespace App\Controller\Api;

use App\Exception\Api\AuthException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\NotificationTypeEnum;
use App\Notification\Producer\SystemProducer;
use App\Security\AccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\NotFoundResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\TokenSchema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints;
use App\Entity;

/**
 * @Route("/registration")
 */
class ConfirmationController
{
    use FormTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var SystemProducer */
    protected $systemProducer;

    /** @var AccessToken */
    protected $accessToken;

    public function __construct(EntityManagerInterface $entityManager, SystemProducer $producer, AccessToken $accessToken)
    {
        $this->entityManager = $entityManager;
        $this->systemProducer = $producer;
        $this->accessToken = $accessToken;
    }

    /**
     * @SWG\Post(
     *
     *  path = "/registration/employees/activate",
     *  summary = "Активация аккаунта сотрудника кодом из email",
     *  description = "",
     *  tags = { "Registration" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "code" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "code", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Аккаунт успешно активирован и получен новый токен доступа",
     *      @TokenSchema()
     *  ),
     *
     *  @BadRequestResponse(),
     *  @NotFoundResponse()
     * )
     *
     * @Route("/employees/activate", methods = { "POST" })
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws AuthException
     * @throws \App\Exception\Api\FormValidationException
     */
    public function confirmEmailAction(Request $request): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('email', Type\TextType::class, ['constraints' => [new Constraints\Email(), new Constraints\NotBlank()]])
            ->add('code',  Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        /** @var Entity\UserConfirmation $confirmation */
        $confirmation = $this->entityManager->getRepository('App:UserConfirmation')->findOneBy(['email' => strtolower($data['email'])]);
        if (null === $confirmation) {
            throw new NotFoundHttpException('Не найден email для подтверждения');
        }

        if ($confirmation->getEmailConfirmationCode() !== $data['code']) {
            throw new AuthException('Неверный код подтверждения');
        }

        // В случае успешного подтверждения email
        // активируем пользователя

        $user = $confirmation->getUser();
        $user->setEmail($confirmation->getEmail());
        $user->setActive(true);

        // Запишем данные, когда email был подтвержден

        $confirmation->setEmailConfirmationTime(new \DateTime());
        $confirmation->setEmailConfirmed(true);
        $confirmation->setEmailConfirmationCode(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Отправим уведомление о регистрации сотрудника

        $employer = $user->getProfile()->getEmployer();

        $this->systemProducer->produce(NotificationTypeEnum::NEW_EMPLOYEE(), [
            'subject' => 'Зарегистрировался новый сотрудник продавца',
            'email'   => strtolower($data['email']),
            'company' => $employer ? $employer->getProfile()->getCompanyTitle() : null
        ]);

        return new JsonResponse(
            ['token' => $this->accessToken->create($user->getEmail(), $user->getTokenSalt())],
            JsonResponse::HTTP_ACCEPTED
        );
    }
}