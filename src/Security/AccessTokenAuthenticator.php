<?php

namespace App\Security;

use App\Entity\User;
use App\Exception\AccessTokenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class AccessTokenAuthenticator implements SimplePreAuthenticatorInterface
{
    /**
     * @param TokenInterface $token
     * @param UserProviderInterface $userProvider
     * @param $providerKey
     * @return PreAuthenticatedToken
     * @throws \InvalidArgumentException
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey): AbstractToken
    {
        if (!$userProvider instanceof AccessTokenUserProvider) {
            throw new \InvalidArgumentException(sprintf(
                'The user provider must be an instance of AccessTokenUserProvider (%s was given).',
                \get_class($userProvider)
            ));
        }

        try {

            $accessToken = $token->getCredentials();
            $username    = $userProvider->getUsernameForToken($accessToken);
            $user        = $userProvider->loadUserByUsername($username);
            
            return new PreAuthenticatedToken(
                $user,
                $accessToken,
                $providerKey,
                $user->getRoles()
            );

        } catch (AccessTokenException|UsernameNotFoundException $exception) {

            // Если проблема с токеном или не нашли пользователя
            // будем считать его анонимным
            return new AnonymousToken('', new User());
        }
    }

    public function supportsToken(TokenInterface $token, $providerKey): bool
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function createToken(Request $request, $providerKey)
    {
        $authorizationHeader = $request->headers->get('authorization', '');
        $accessToken = preg_replace('/^bearer /i', '', $authorizationHeader);

        return new PreAuthenticatedToken('anon.', $accessToken, $providerKey);
    }
}