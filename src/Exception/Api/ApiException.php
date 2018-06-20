<?php

namespace App\Exception\Api;

use App\Exception\AppException;

class ApiException extends AppException
{
    protected $errors = [];

    /**
     * AppException constructor.
     * @param string $message
     * @param null|\Exception|array $errors
     */
    public function __construct(string $message, $errors = null)
    {
        if ($errors instanceof \Exception) {
            $this->errors = ['exception' => sprintf('%s: %s', \get_class($errors), $errors->getMessage())];
        }

        if (\is_array($errors)) {
            $this->errors = $errors;
        }

        parent::__construct($message, 0);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}