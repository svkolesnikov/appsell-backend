<?php

namespace App\Security;

use App\Exception\Api\AccessTokenException;
use Firebase\JWT\JWT;

class AccessToken
{
    /** @var int */
    protected $expiresIn;

    /** @var string */
    protected $secret;

    /** @var string */
    protected $algorithm = 'HS256';

    public function __construct(string $secret, int $expiresIn)
    {
        $this->secret    = $secret;
        $this->expiresIn = $expiresIn;
    }

    /**
     * @param string $email
     * @param string|null $salt
     * @return string
     */
    public function create(string $email, string $salt = null): string
    {
        return JWT::encode([
            'email' => $email,
            'salt'  => $salt,
            'iat'   => time(),
            'exp'   => time() + $this->expiresIn
        ], $this->secret, $this->algorithm);
    }

    /**
     * @param string $token
     * @throws AccessTokenException
     * @return string
     */
    public function authorize(string $token): string
    {
        try {
            $decoded = JWT::decode($token, $this->secret, [$this->algorithm]);
        } catch (\Exception $ex) {
            throw new AccessTokenException('Авторизация истекла. Попробуйте войти повторно', $ex);
        }

        if (!$decoded->exp || time() > (int) $decoded->exp) {
            throw new AccessTokenException('Авторизация истекла. Попробуйте войти повторно');
        }

        return sprintf(
            '%s|%s',
            $decoded->email ?? null,
            $decoded->salt  ?? null
        );
    }
}