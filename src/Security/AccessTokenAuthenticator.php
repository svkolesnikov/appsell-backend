<?php

namespace App\Security;

use App\Exception\Api\AccessTokenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
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
     * @throws AccessTokenException
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

        } catch (AccessTokenException|UsernameNotFoundException|DisabledException $exception) {
            throw new AccessTokenException($exception->getMessage(), $exception);
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

        if (!$accessToken) {
            return null;
        }

        return new PreAuthenticatedToken('anon.', $accessToken, $providerKey);
    }
}