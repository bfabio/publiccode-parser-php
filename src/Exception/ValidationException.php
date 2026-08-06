<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser\Exception;

use Bfabio\PublicCodeParser\ValidationError;
use Bfabio\PublicCodeParser\ValidationWarning;

class ValidationException extends ParserException
{
    /** @var list<string> */
    private array $errors;

    /** @var list<ValidationError> */
    private array $validationErrors;

    /** @var list<string> */
    private array $warnings;

    /** @var list<ValidationWarning> */
    private array $validationWarnings;

    /**
     * @param list<string> $errors
     * @param list<ValidationError> $validationErrors
     * @param list<string> $warnings
     * @param list<ValidationWarning> $validationWarnings
     */
    public function __construct(
        string $message,
        array $errors = [],
        ?\Throwable $previous = null,
        array $validationErrors = [],
        array $warnings = [],
        array $validationWarnings = [],
    ) {
        parent::__construct($message, 0, $previous);
        $this->errors = $errors;
        $this->validationErrors = $validationErrors;
        $this->warnings = $warnings;
        $this->validationWarnings = $validationWarnings;
    }

    /** @return list<string> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return list<ValidationError> */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /** @return list<string> */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /** @return list<ValidationWarning> */
    public function getValidationWarnings(): array
    {
        return $this->validationWarnings;
    }
}
