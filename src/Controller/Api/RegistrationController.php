<?php

namespace App\Controller\Api;

use App\Exception\Api\ApiException;
use App\Exception\Api\AuthException;
use App\Exception\AppException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\NotificationTypeEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Notification\Producer\ClientProducer;
use App\Notification\Producer\SystemProducer;
use App\Security\UserGroupManager;
use App\SolarStaff\Client;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
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
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
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
     * @throws \App\Exception\Api\FormValidationException
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
        $user->setEmail(strtolower($data['email']));

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
            'email'   => $user->getEmail(),
            'phone'   => $profile->getPhone()
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
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
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
     * @throws \App\Exception\Api\FormValidationException
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
        $user->setEmail(strtolower($data['email']));

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
            'email'   => $user->getEmail(),
            'phone'   => $profile->getPhone()
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
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "password", "company_id" },
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
     * @param Client $ssClient
     * @return JsonResponse
     * @throws ApiException
     * @throws AuthException
     * @throws \App\Exception\Api\FormValidationException
     */
    public function registerEmployeeAction(
        Request $request,
        UserPasswordEncoderInterface $encoder,
        UserGroupManager $groupManager,
        Client $ssClient
    ): JsonResponse
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
        if (null === $employer || !$groupManager->hasGroup($employer->getUser(), UserGroupEnum::SELLER())) {
            throw new AuthException('Неверный идентификатор компании');
        }

        // Создадим пользователя

        $user = new Entity\User();
        $user->setEmail(strtolower($data['email']));
        $user->setPassword($encoder->encodePassword($user, $data['password']));

        try {
            $groupManager->addGroup($user, UserGroupEnum::EMPLOYEE());
        } catch (AppException $ex) {
            throw new ApiException($ex->getMessage(), $ex);
        }

        $confirmation = $user->getConfirmation();
        $confirmation->setEmail($user->getEmail());
        $confirmation->setEmailConfirmationCode(random_int(111111, 999999));

        $profile = $user->getProfile();
        $profile->setEmployer($employer->getUser());

        $this->save($profile);

        // Если компания оплачивает работы сотрудников через solar staff
        // тогда нужно так же создать пользователя и в этом сервисе

        if ($employer->isCompanyPayoutOverSolarStaff()) {

            // После успешного сохранения зарегистрируем в Solar-Staff
            // и запишем ID сотрудника из SS в профиль

            $profile->setSolarStaffId($ssClient->createWorker($user->getEmail()));
            $this->save($profile);
        }

        // Отправим уведомление с кодом

        $this->clientProducer->produce(NotificationTypeEnum::CONFIRM_EMAIL(), [
            'subject' => 'Код активации email на сервисе AppSell',
            'code' => $confirmation->getEmailConfirmationCode(),
            'to' => $user->getEmail()
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

            $this->entityManager->persist($profile->getUser());
            $this->entityManager->flush();

        } catch (UniqueConstraintViolationException $ex) {
            throw new AuthException('Указанный адрес электронной почты уже зарегистрирован', $ex);
        }
    }
}