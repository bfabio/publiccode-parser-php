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
    private Parser $parserNoNetwork;

    protected function setUp(): void
    {
        $this->parser = new Parser();

        $opts = new ParserConfig();
        $opts->setDisableNetwork(true);
        $this->parserNoNetwork = new Parser($opts);
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
     * @dataProvider validFilesNoNetworkProvider
     */
    public function testValidFilesNoNetworkParseWithoutError(string $yamlPath): void
    {
        $pc = $this->parserNoNetwork->parseFile($yamlPath);
        $this->assertNotNull($pc, $yamlPath);

        $this->assertTrue($this->parserNoNetwork->isValid($yamlPath));
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
     * @dataProvider invalidFilesNoNetworkProvider
     */
    public function testInvalidFilesNoNetworkRaiseValidationException(string $yamlPath): void
    {
        $this->expectException(ValidationException::class);
        $this->parserNoNetwork->parseFile($yamlPath);
    }

    /**
     * @dataProvider invalidFilesProvider
     */
    public function testInvalidFilesIsValid(string $yamlPath): void
    {
        $this->assertFalse($this->parser->isValid($yamlPath));
    }

    /**
     * @return non-empty-array<string, array{string}>
     */
    public static function validFilesProvider(): array
    {
        return self::scanTestdata(['valid', 'valid_with_warnings']);
    }

    /**
     * @return non-empty-array<string, array{string}>
     */
    public static function validFilesNoNetworkProvider(): array
    {
        return self::scanTestdata(['valid/no-network', 'valid_with_warnings/no-network']);
    }
    /**
     * @return non-empty-array<string, array{string}>
     */
    public static function invalidFilesProvider(): array
    {
        return self::scanTestdata(['invalid']);
    }

    /**
     * @return non-empty-array<string, array{string}>
     */
    public static function invalidFilesNoNetworkProvider(): array
    {
        return self::scanTestdata(['invalid/no-network']);
    }

    /**
     * @param list<non-empty-string> $paths
     * @return non-empty-array<string, array{string}>
     */
    private static function scanTestdata(array $paths): array
    {
        $root = __DIR__ . '/fixtures/testdata';
        $out = [];

        foreach (['v0'] as $v) {
            foreach ($paths as $path) {
                foreach (glob("$root/$v/$path/*.yml") ?: [] as $file) {
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
