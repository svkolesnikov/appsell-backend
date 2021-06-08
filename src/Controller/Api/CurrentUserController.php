<?php

namespace App\Controller\Api;

use App\Exception\Api\ApiException;
use App\Exception\Api\FormValidationException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\UserGroupEnum;
use App\Security\AccessToken;
use App\Security\UserGroupManager;
use App\SolarStaff\Client;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\NotFoundResponse;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\UserSchema;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\TokenSchema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints;

/**
 * @Route("/users/current")
 */
class CurrentUserController
{
    use FormTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage, LoggerInterface $logger)
    {
        $this->entityManager = $em;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * @SWG\Get(
     *
     *  path = "/users/current",
     *  summary = "Получение профиля текущего пользователя",
     *  description = "",
     *  tags = { "Users" },
     *
     *  @TokenParameter(),
     *
     *  @SWG\Response(
     *      response = 200,
     *      description = "Профиль получен",
     *      @UserSchema()
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @NotFoundResponse(),
     * )
     *
     * @Route("", methods = { "GET" })
     * @return JsonResponse
     */
    public function getCurrentUserAction(): JsonResponse
    {
        /** @var Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $profile = $user->getProfile();

        $companyInfo = null === $profile->getEmployer()
            ? $profile
            : $profile->getEmployer()->getProfile();

        $baseGroup = null;
        foreach ($user->getGroups() as $group) {
            if (\in_array($group->getCode(), UserGroupEnum::toArray(), true)) {
                $baseGroup = $group->getCode();
                break;
            }
        }

        return new JsonResponse([
            'email'                     => $user->getEmail(),
            'seller_id'                 => $user->getId(),
            'group'                     => $baseGroup,
            'phone'                     => $profile->getPhone(),
            'lastname'                  => $profile->getLastname(),
            'firstname'                 => $profile->getFirstname(),
            'company_id'                => $companyInfo->getCompanyId(),
            'company_title'             => $companyInfo->getCompanyTitle(),
            'is_solar_staff_connected'  => $profile->isSolarStaffConnected()
        ]);
    }

    /**
     * @SWG\Put(
     *
     *  path = "/users/current",
     *  summary = "Обновление данных профиля текущего пользователя",
     *  description = "",
     *  tags = { "Users" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { },
     *      properties = {
     *          @SWG\Property(property = "company_id", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 204,
     *      description = "Данные профиля обновлены"
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse(),
     * )
     *
     * @Route("", methods = { "PUT" })
     * @param Request $request
     * @param UserGroupManager $groupManager
     * @param Client $ssClient
     * @return JsonResponse
     * @throws FormValidationException
     */
    public function updateProfileAction(Request $request, UserGroupManager $groupManager, Client $ssClient): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('company_id', Type\TextType::class)
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        /** @var Entity\User $user */
        $user             = $this->tokenStorage->getToken()->getUser();
        $currentEmployeer = $user->getProfile()->getEmployer();

        // Если был прислан новый код компании
        // Если пользователь при этом является сотрудником компании SolarStaff
        // То если новая компания указана как "выводящая деньги чере SS",
        // привяжем пользователя к новой компании

        if (null !== $data['company_id']) {

            $isSolarStaffEmployee =
                $groupManager->hasGroup($user, UserGroupEnum::EMPLOYEE())       // Это сотрудник
                && null !== $currentEmployeer                                   // Есть компания к которой он привязан
                && $ssClient->getEmployerId() === $currentEmployeer->getId()    // Эта компания – SolarStaff
                && $user->getProfile()->isSolarStaffConnected();                // Профиль уже привязан к SolarStaff

            if ($isSolarStaffEmployee) {

                /** @var Entity\UserProfile $employer */
                $employer = $this->entityManager->getRepository('App:UserProfile')->findOneBy(['company_id' => $data['company_id']]);
                if (null === $employer || !$groupManager->hasGroup($employer->getUser(), UserGroupEnum::SELLER())) {
                    throw new FormValidationException(
                        'Неверные данные',
                        ['company_id' => 'Неверный ID компании']
                    );
                }

                if ($employer->isCompanyPayoutOverSolarStaff()) {
                    $user->getProfile()->setEmployer($employer->getUser());

                    $this->entityManager->persist($user->getProfile());
                    $this->entityManager->flush();
                } else {
                    throw new FormValidationException('Невозможно привязать к профилю данную компанию');
                }

            } else {

                $this->logger->warning('Неудачная попытка пользователя привязать себя к компании продавцу', [
                    'user_id'         => $user->getId(),
                    'is_employee'     => $groupManager->hasGroup($user, UserGroupEnum::EMPLOYEE()),
                    'has_employeer'   => null !== $currentEmployeer,
                    'is_employeer_ss' => $ssClient->getEmployerId() === $currentEmployeer->getId(),
                    'is_ss_connected' => $user->getProfile()->isSolarStaffConnected(),
                    'new_company_id'  => $data['company_id']
                ]);

                throw new FormValidationException(
                    'Только сотрудник, зарегистрированный через SolarStaff может изменить ID компании в профиле'
                );
            }
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @SWG\Put(
     *
     *  path = "/users/current/password",
     *  summary = "Изменение пароля текущего пользователя",
     *  description = "",
     *  tags = { "Users" },
     *
     *  @TokenParameter(),
     *  @SWG\Parameter(name = "request", description = "Запрос", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "password", "new_password" },
     *      properties = {
     *          @SWG\Property(property = "password", type = "string"),
     *          @SWG\Property(property = "new_password", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Пароль изменен. Выдан новый токен доступа",
     *      @TokenSchema()
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse(),
     * )
     *
     * @Route("/password", methods = { "PUT" })
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @param AccessToken $accessToken
     * @return JsonResponse
     * @throws ApiException
     * @throws FormValidationException
     */
    public function changeCurrentUserPasswordAction(Request $request, UserPasswordEncoderInterface $encoder, AccessToken $accessToken): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('password',     Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('new_password', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        /** @var Entity\User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$encoder->isPasswordValid($user, $data['password'])) {
            throw new ApiException('Неверно указан текущий пароль');
        }

        $user->setPassword($encoder->encodePassword($user, $data['new_password']));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(
            ['token' => $accessToken->create($user->getEmail(), $user->getTokenSalt())],
            JsonResponse::HTTP_CREATED
        );
    }
}