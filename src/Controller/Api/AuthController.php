<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Exception\Api\AuthException;
use App\Lib\Controller\FormTrait;
use App\Security\AccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\BadRequestResponse;
use App\Swagger\Annotations\TokenParameter;
use App\Swagger\Annotations\TokenSchema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/auth")
 */
class AuthController
{
    use FormTrait;

    /** @var AccessToken */
    protected $accessToken;

    public function __construct(AccessToken $at)
    {
        $this->accessToken = $at;
    }

    /**
     * @SWG\Post(
     *
     *  path = "/auth/login",
     *  summary = "Аутентификация по логину и паролю",
     *  description = "Возвращает новый токен доступа если учетные данные верны",
     *  tags = { "Authorization" },
     *
     *  @SWG\Parameter(name = "request", description = "Запрос", type = "object", required = true, in = "body",
     *     @SWG\Schema(
     *      type = "object",
     *      required = { "email", "password" },
     *      properties = {
     *          @SWG\Property(property = "email", type = "string"),
     *          @SWG\Property(property = "password", type = "string")
     *      }
     *     )
     *  ),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Создан новый токен доступа",
     *      @TokenSchema()
     *  ),
     *
     *  @BadRequestResponse(),
     *  @AccessDeniedResponse()
     * )
     *
     * @Route("/login", methods = { "POST" })
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws AuthException
     * @throws \App\Exception\Api\FormValidationException
     */
    public function loginAction(Request $request, UserPasswordEncoderInterface $encoder, EntityManagerInterface $em): JsonResponse
    {
        $form = $this->createFormBuilder()
            ->setMethod($request->getMethod())
            ->add('email',    Type\TextType::class, ['constraints' => [new Constraints\Email(), new Constraints\NotBlank()]])
            ->add('password', Type\TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();

        $form->handleRequest($request);
        $this->validateForm($form);
        $data = $form->getData();

        /** @var User $user */
        $user = $em->getRepository('App:User')->findOneBy(['email' => $data['email']]);
        if (null === $user || !$encoder->isPasswordValid($user, $data['password'])) {
            throw new AuthException('Неверный email или пароль');
        }

        if (!$user->isActive()) {
            throw new AccessDeniedHttpException('Аккаунт заблокирован');
        }

        return new JsonResponse(
            ['token' => $this->accessToken->create($user->getEmail())],
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * @SWG\Post(
     *
     *  path = "/auth/token",
     *  summary = "Обновление токена доступа",
     *  description = "Возвращает новый токен доступа если пользователь был успешно авторизован",
     *  tags = { "Authorization" },
     *
     *  @TokenParameter(),
     *
     *  @SWG\Response(
     *      response = 201,
     *      description = "Создан новый токен доступа",
     *      @TokenSchema()
     *  ),
     *
     *  @UnauthorizedResponse(),
     *  @AccessDeniedResponse()
     * )
     *
     * @Route("/token", methods = { "POST" })
     * @param TokenStorageInterface $tokenStorage
     * @return JsonResponse
     */
    public function refreshTokenAction(TokenStorageInterface $tokenStorage): JsonResponse
    {
        /** @var User $user */
        $user = $tokenStorage->getToken()->getUser();

        return new JsonResponse(
            ['token' => $this->accessToken->create($user->getEmail())],
            JsonResponse::HTTP_CREATED
        );
    }
}