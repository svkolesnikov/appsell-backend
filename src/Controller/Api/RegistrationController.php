<?php

namespace App\Controller\Api;

use App\Exception\AuthException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\NotificationTypeEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Notification\Producer\ClientProducer;
use App\Notification\Producer\SystemProducer;
use App\Security\UserGroupManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Swagger\Annotations\BadRequestResponse;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints;

/**
 * @Route("/registration")
 */
class RegistrationController
{
    use FormTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var SystemProducer */
    protected $systemProducer;

    /** @var ClientProducer */
    protected $clientProducer;

    public function __construct(EntityManagerInterface $em, SystemProducer $sp, ClientProducer $cp)
    {
        $this->entityManager = $em;
        $this->systemProducer = $sp;
        $this->clientProducer = $cp;
    }

    /**
     * @SWG\Post(
     *
     *  path = "/registration/sellers",
     *  summary = "Регистрация продавца",
     *  description = "",
     *  tags = { "Registration" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", type = "object", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "phone" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "phone", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Пользователь зарегистрирован"
     *  ),
     *
     *  @BadRequestResponse()
     * )
     *
     * @Route("/sellers", methods = { "POST" })
     * @param Request $request
     * @return JsonResponse
     * @throws \App\Exception\FormValidationException
     */
    public function registerSellerAction(Request $request): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('email', Type\TextType::class, ['constraints' => [new Constraints\Email(), new Constraints\NotBlank()]])
            ->add('phone', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        $user = new Entity\User();
        $user->setEmail($data['email']);

        $profile = $user->getProfile();
        $profile->setPhone($data['phone']);

        try {
            $this->save($profile);
        } catch (AuthException $ex) {
            // В данном случае может возникнуть ситуация, что про заявку все забыли
            // а чувак снова пытается зарегаться - так что, если у нас уже есть
            // этот пользователь - вышлем уведомление повторно
        }

        $this->systemProducer->produce(NotificationTypeEnum::NEW_SELLER(), [
            'subject' => 'Зарегистрировался новый продавец',
            'email'   => $data['email'],
            'phone'   => $data['phone']
        ]);

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    /**
     * @SWG\Post(
     *
     *  path = "/registration/owners",
     *  summary = "Регистрация заказчика",
     *  description = "",
     *  tags = { "Registration" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", type = "object", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "phone" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "phone", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Пользователь зарегистрирован"
     *  ),
     *
     *  @BadRequestResponse()
     * )
     *
     * @Route("/owners", methods = { "POST" })
     * @param Request $request
     * @return JsonResponse
     * @throws \App\Exception\FormValidationException
     */
    public function registerOwnerAction(Request $request): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('email', Type\TextType::class, ['constraints' => [new Constraints\Email(), new Constraints\NotBlank()]])
            ->add('phone', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        $user = new Entity\User();
        $user->setEmail($data['email']);

        $profile = $user->getProfile();
        $profile->setPhone($data['phone']);

        try {
            $this->save($profile);
        } catch (AuthException $ex) {
            // В данном случае может возникнуть ситуация, что про заявку все забыли
            // а чувак снова пытается зарегаться - так что, если у нас уже есть
            // этот пользователь - вышлем уведомление повторно
        }

        $this->systemProducer->produce(NotificationTypeEnum::NEW_OWNER(), [
            'subject' => 'Зарегистрировался новый заказчик',
            'email'   => $data['email'],
            'phone'   => $data['phone']
        ]);

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    /**
     * @SWG\Post(
     *
     *  path = "/registration/employees",
     *  summary = "Регистрация сотрудника продавца",
     *  description = "",
     *  tags = { "Registration" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", type = "object", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "phone" , "company_id" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "password", type = "string"),
     *          @SWG\Property(property = "company_id", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Пользователь зарегистрирован"
     *  ),
     *
     *  @BadRequestResponse()
     * )
     *
     * @Route("/employees", methods = { "POST" })
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @param UserGroupManager $groupManager
     * @return JsonResponse
     * @throws AuthException
     * @throws \App\Exception\FormValidationException
     * @throws \App\Exception\AppException
     */
    public function registerEmployeeAction(Request $request, UserPasswordEncoderInterface $encoder, UserGroupManager $groupManager): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('email',      Type\TextType::class, ['constraints' => [new Constraints\Email(), new Constraints\NotBlank()]])
            ->add('password',   Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('company_id', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        /** @var Entity\UserProfile $employer */
        $employer = $this->entityManager->getRepository('App:UserProfile')->findOneBy(['company_id' => $data['company_id']]);
        if (null === $employer) {
            throw new AuthException('Неверный идентификатор компании');
        }

        // Сгенерируем код подтверждения

        $confirmationCode = new Entity\ConfirmationCode();
        $confirmationCode->setSubject($data['email']);
        $confirmationCode->setCode(random_int(111111, 999999));
        $this->entityManager->persist($confirmationCode);

        // Создадим пользователя

        $user = new Entity\User();
        $user->setEmail($data['email']);
        $user->setPassword($encoder->encodePassword($user, $data['password']));
        $groupManager->addGroup($user, UserGroupEnum::EMPLOYEE());

        $profile = $user->getProfile();
        $profile->setEmployer($employer->getUser());

        $this->save($profile);

        // Отправим уведомление с кодом

        $this->clientProducer->produce(NotificationTypeEnum::CONFIRM_EMAIL(), [
            'subject' => 'Код активации email на сервисе AppSell',
            'code' => $confirmationCode->getCode()
        ]);

        return new JsonResponse(null, JsonResponse::HTTP_CREATED);
    }

    /**
     * @param Entity\UserProfile $profile
     * @throws AuthException
     */
    private function save(Entity\UserProfile $profile): void
    {
        try {

            $this->entityManager->persist($profile);
            $this->entityManager->persist($profile->getUser());
            $this->entityManager->flush();

        } catch (UniqueConstraintViolationException $ex) {
            throw new AuthException('Email already exists', $ex);
        }
    }
}