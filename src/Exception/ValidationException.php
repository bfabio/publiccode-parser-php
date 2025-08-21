<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser\Exception;

class ValidationException extends ParserException
{
	/** @var list<string> */
	private array $errors;

	/**
	 * @param list<string> $errors
	 */
	public function __construct(string $message, array $errors = [], ?\Throwable $previous = null)
	{
		parent::__construct($message, 0, $previous);
		$this->errors = $errors;
	}

	/** @return list<string> */
	public function getErrors(): array
	{
		return $this->errors;
	}
}
