<?php

namespace App\Security;

use App\Exception\AccessTokenException;
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
     * @return string
     */
    public function create(string $email): string
    {
        return JWT::encode([
            'email' => $email,
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
            throw new AccessTokenException('Invalid access token', 0, $ex);
        }

        if (!$decoded->exp || time() > (int) $decoded->exp) {
            throw new AccessTokenException('Access token is expired');
        }

        return (string) $decoded->email;
    }
}