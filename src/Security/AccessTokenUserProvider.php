<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AccessTokenUserProvider implements UserProviderInterface
{
    /** @var AccessToken */
    protected $accessToken;

    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(AccessToken $at, EntityManagerInterface $em)
    {
        $this->accessToken = $at;
        $this->entityManager = $em;
    }

    /**
     * @param string $token
     * @return string
     * @throws \App\Exception\Api\AccessTokenException
     */
    public function getUsernameForToken(string $token): string
    {
        return $this->accessToken->authorize($token);
    }

    /**
     * @param string $username
     * @return UserInterface
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Symfony\Component\Security\Core\Exception\DisabledException
     * @throws UsernameNotFoundException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByUsername($username): UserInterface
    {
        [$email, $tokenSalt] = explode('|', $username);

        /** @var User $user */
        $user = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from('App:User', 'u')
            ->where('u.email = :email and (u.token_salt is null or u.token_salt = :salt)')
            ->setParameters([
                'email' => $email,
                'salt' => $tokenSalt
            ])
            ->getQuery()
            ->getSingleResult();

        if (null === $user) {
            $ex = new UsernameNotFoundException();
            $ex->setUsername($username);
            throw $ex;
        }

        if (!$user->isActive()) {
            throw new DisabledException('Аккаунт заблокирован');
        }

        return $user;
    }

    /**
     * @param UserInterface $user
     * @return UserInterface
     * @throws UnsupportedUserException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        throw new UnsupportedUserException();
    }

    public function supportsClass($class): bool
    {
        return User::class === $class;
    }
}