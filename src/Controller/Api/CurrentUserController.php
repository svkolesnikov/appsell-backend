<?php

namespace App\Controller\Api;

use App\Exception\Api\ApiException;
use App\Lib\Controller\FormTrait;
use App\Lib\Enum\UserGroupEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\NotFoundResponse;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\UserSchema;
use App\Swagger\Annotations\TokenParameter;
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

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
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
     * @param TokenStorageInterface $tokenStorage
     * @return JsonResponse
     */
    public function getCurrentUserAction(TokenStorageInterface $tokenStorage): JsonResponse
    {
        /** @var Entity\User $user */
        $user = $tokenStorage->getToken()->getUser();
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
            'email' => $user->getEmail(),
            'group' => $baseGroup,
            'phone' => $profile->getPhone(),
            'lastname' => $profile->getLastname(),
            'firstname' => $profile->getFirstname(),
            'company_id' => $companyInfo->getCompanyId(),
            'company_title' => $companyInfo->getCompanyTitle()
        ]);
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
     *      response = 202,
     *      description = "Пароль изменен"
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse(),
     *  @BadRequestResponse(),
     * )
     *
     * @Route("/password", methods = { "PUT" })
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     * @throws \App\Exception\Api\FormValidationException
     * @throws ApiException
     */
    public function changeCurrentUserPasswordAction(Request $request, TokenStorageInterface $tokenStorage, UserPasswordEncoderInterface $encoder): JsonResponse
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
        $user = $tokenStorage->getToken()->getUser();

        if (!$encoder->isPasswordValid($user, $data['password'])) {
            throw new ApiException('Неверно указан текущий пароль');
        }

        $user->setPassword($encoder->encodePassword($user, $data['new_password']));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_ACCEPTED);
    }
}