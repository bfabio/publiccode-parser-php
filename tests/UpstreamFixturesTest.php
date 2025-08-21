<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser\Tests;

use Bfabio\PublicCodeParser\Exception\ValidationException;
use Bfabio\PublicCodeParser\Parser;
use Bfabio\PublicCodeParser\ParserConfig;
use PHPUnit\Framework\TestCase;

final class UpstreamFixturesTest extends TestCase
{
	private Parser $parser;

	protected function setUp(): void
	{
		$opts = new ParserConfig();
		$opts->setDisableNetwork(true);

		$this->parser = new Parser($opts);
	}

	/**
	 * @dataProvider validFilesProvider
	 */
	public function testValidFilesParseWithoutError(string $yamlPath): void
	{
		$pc = $this->parser->parseFile($yamlPath);
		$this->assertNotNull($pc, $yamlPath);

		$this->assertTrue($this->parser->isValid($yamlPath));
	}

	/**
	 * @dataProvider invalidFilesProvider
	 */
	public function testInvalidFilesRaiseValidationException(string $yamlPath): void
	{
		$this->expectException(ValidationException::class);
		$this->parser->parseFile($yamlPath);
	}

	/**
	 * @dataProvider invalidFilesProvider
	 */
	public function testInvalidFilesIsValid(string $yamlPath): void
	{
		$this->assertFalse($this->parser->isValid($yamlPath));
	}

	public static function validFilesProvider(): array
	{
		// TODO: re-enable no-network tests
		// return self::scanTestdata(['valid', 'valid_with_warnings', 'valid/no-network', 'valid_with_warnings/no-network']);
		return self::scanTestdata(['valid', 'valid_with_warnings']);
	}

	public static function invalidFilesProvider(): array
	{
		return self::scanTestdata(['invalid', 'invalid/no-network']);
	}

	/** @return list<array{string}> */
	private static function scanTestdata(array $paths): array
	{
		$root = __DIR__ . '/fixtures/testdata';
		$out = [];

		foreach (['v0'] as $v) {
			foreach ($paths as $path) {
				foreach (glob("$root/$v/$path/*.yml") as $file) {
					$out[$file] = [$file];
				}
			}
		}
		if (!$out) {
			self::fail("Nessun file trovato in $root");
		}

		return $out;
	}
}
