<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser\Exception;

class ValidationException extends ParserException
{
    private array $errors;

    public function __construct(string $message, array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
