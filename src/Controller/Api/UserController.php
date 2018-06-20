<?php

namespace App\Controller\Api;

use App\Lib\Enum\UserGroupEnum;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use App\Swagger\Annotations\AccessDeniedResponse;
use App\Swagger\Annotations\UnauthorizedResponse;
use App\Swagger\Annotations\NotFoundResponse;
use App\Swagger\Annotations\UserSchema;
use App\Swagger\Annotations\TokenParameter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity;

/**
 * @Route("/users")
 */
class UserController
{
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
     * @Route("/current", methods = { "GET" })
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
}