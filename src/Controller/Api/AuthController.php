<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Exception\AccessException;
use App\Exception\AuthException;
use App\Lib\Controller\FormTrait;
use App\Security\AccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
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

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var AccessToken */
    protected $accessToken;

    /** @var UserPasswordEncoderInterface */
    protected $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $encoder, EntityManagerInterface $em, AccessToken $at)
    {
        $this->passwordEncoder = $encoder;
        $this->entityManager = $em;
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
     *  @BadRequestResponse()
     * )
     *
     * @Route("/login", methods = { "POST" })
     * @param Request $request
     * @return JsonResponse
     * @throws \App\Exception\FormValidationException
     * @throws AuthException
     * @throws AccessException
     */
    public function loginAction(Request $request): JsonResponse
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
        $user = $this->entityManager->getRepository('App:User')->findOneBy(['email' => $data['email']]);
        if (null === $user || !$this->passwordEncoder->isPasswordValid($user, $data['password'])) {
            throw new AuthException('Неверный email или пароль');
        }

        if (!$user->isActive()) {
            throw new AccessException('Аккаунт заблокирован');
        }

        return new JsonResponse(
            ['token' => $this->accessToken->create($user->getEmail())],
            JsonResponse::HTTP_CREATED
        );
    }
}