<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser\Tests;

use Bfabio\PublicCodeParser\Exception\ValidationException;
use Bfabio\PublicCodeParser\ValidationError;
use Bfabio\PublicCodeParser\ValidationWarning;
use PHPUnit\Framework\TestCase;

final class ValidationResultTest extends TestCase
{
    public function testLegacyValidationExceptionApiRemainsUnchanged(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new ValidationException('Invalid', ['Legacy error'], $previous);

        $this->assertSame(['Legacy error'], $exception->getErrors());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame([], $exception->getValidationErrors());
        $this->assertSame([], $exception->getWarnings());
        $this->assertSame([], $exception->getValidationWarnings());
    }

    public function testLegacyErrorCanBeConvertedToStructuredError(): void
    {
        $message = 'publiccode.yml:4:1: error: applicationSuite: wrong type for this field';
        $error = ValidationError::fromString($message);

        $this->assertSame('error', $error->getType());
        $this->assertSame(4, $error->getLine());
        $this->assertSame(1, $error->getColumn());
        $this->assertSame('applicationSuite', $error->getKey());
        $this->assertSame('wrong type for this field', $error->getDescription());
        $this->assertSame($message, (string) $error);
    }

    public function testLegacyWarningCanBeConvertedToStructuredWarning(): void
    {
        $message = 'publiccode.yml:12:5: warning: description.en.genericName: Deprecated field';
        $warning = ValidationWarning::fromString($message);

        $this->assertSame('warning', $warning->getType());
        $this->assertSame(12, $warning->getLine());
        $this->assertSame(5, $warning->getColumn());
        $this->assertSame('description.en.genericName', $warning->getKey());
        $this->assertSame('Deprecated field', $warning->getDescription());
        $this->assertSame($message, (string) $warning);
    }
}
