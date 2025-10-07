<?php

/**
 * Check the actual errors/warnings from the Go implementation, using
 * `publiccode-parser --json` as a reference so an check that the binding
 * behaves exactly like it should without having to duplicate the Go
 * testcases here.
 */

declare(strict_types=1);

namespace Bfabio\PublicCodeParser\Tests;

use Bfabio\PublicCodeParser\Exception\ValidationException;
use Bfabio\PublicCodeParser\Parser;
use Bfabio\PublicCodeParser\ParserConfig;
use PHPUnit\Framework\TestCase;

final class ParserGoldenFilesTest extends TestCase
{
    private const ROOT = __DIR__ . '/fixtures/testdata/v0';
    private const GOLDEN_DIR = self::ROOT . '.golden';

    private Parser $parser;
    private Parser $parserNoNetwork;

    protected function setUp(): void
    {
        if (!is_dir(self::GOLDEN_DIR)) {
            mkdir(self::GOLDEN_DIR, 0755, true);
        }

        $this->parser = new Parser();

        $opts = new ParserConfig();
        $opts->setDisableNetwork(true);
        $this->parserNoNetwork = new Parser($opts);
    }

    /** @dataProvider validFilesWithWarningsProvider */
    /* public function testGenerateGoldenValidWithWarnings(string $yamlPath): void */
    /* { */
    /*     $this->generateGolden($yamlPath); */
    
    /*     $pc = $this->parser->parseFile($yamlPath); */
    
    /*     $pc->getWarnings() */
    /* } */

    /** @dataProvider validFilesWithWarningsNoNetworkProvider */
    /* public function testGenerateGoldenValidWithWarningsNoNetwork(string $yamlPath): void */
    /* { */
    /*     $this->generateGolden($yamlPath, true); */
    /*     $this->assertFileExists($this->goldenPath($yamlPath)); */
    /* } */

    /** @dataProvider invalidFilesProvider */
    public function testGenerateGoldenInvalid(string $yamlPath): void
    {
        $goldenPath = $this->generateGolden($yamlPath);

        try {
            $this->parser->parseFile($yamlPath);

            static::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertErrorsMatchGolden($e, $goldenPath);
        }
    }

    /** @dataProvider invalidFilesNoNetworkProvider */
    public function testGenerateGoldenInvalidNoNetwork(string $yamlPath): void
    {
        $goldenPath = $this->generateGolden($yamlPath, true);

        try {
            $this->parserNoNetwork->parseFile($yamlPath);

            static::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertErrorsMatchGolden($e, $goldenPath);
        }
    }

    /** @return non-empty-array<string, array{string}> */
    // Enable when https://github.com/bfabio/publiccode-parser-php/issues/11 is fixed
    /* public static function validFilesWithWarningsProvider(): array */
    /* { */
    /*     // no 'valid/' directory here because those files don't have any errors or warnings */
    /*     return self::scanTestdata(['valid_with_warnings']); */
    /* } */

    /** @return non-empty-array<string, array{string}> */
    // Enable when https://github.com/bfabio/publiccode-parser-php/issues/11 is fixed
    /* public static function validFilesWithWarningsNoNetworkProvider(): array */
    /* { */
    /*     // no 'valid/no-network' directory here because those files don't have any errors or warnings */
    /*     return self::scanTestdata(['valid/no-network', 'valid_with_warnings/no-network']); */
    /* } */

    /** @return non-empty-array<string, array{string}> */
    public static function invalidFilesProvider(): array
    {
        return self::scanTestdata(['invalid']);
    }

    /** @return non-empty-array<string, array{string}> */
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

    private function goldenPath(string $yamlPath): string
    {
        $relYaml = substr($yamlPath, strlen(self::ROOT) + 1);
        $outFile = self::GOLDEN_DIR . "/$relYaml.json";

        $outDir = dirname($outFile);
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        return $outFile;
    }

    private function generateGolden(string $yamlPath, bool $noNetwork = false): string
    {
        $outFile = $this->goldenPath($yamlPath);

        $cmd = sprintf(
            "publiccode-parser --json %s %s > $outFile 2>&1",
            $noNetwork ? '--no-network' : '',
            escapeshellarg($yamlPath),
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException("publiccode-parser failed on $yamlPath (exit $exitCode)");
        }

        return $outFile;
    }

    private function assertErrorsMatchGolden(ValidationException $e, string $goldenPath): void
    {
        $content = file_get_contents($goldenPath);
        if ($content === false) {
            throw new \RuntimeException("Cannot read file: $goldenPath");
        }

        $golden = json_decode($content, true);

        $goldenMessages = array_map(
            fn(array $g) => sprintf(
                'publiccode.yml:%d:%d: %s: %s%s',
                $g['line'],
                $g['column'],
                $g['type'],
                $g['key'] !== '' ? $g['key'] . ': ' : '',
                $g['description'],
            ),
            array_filter($golden, fn(array $g) => $g['type'] === 'error'),
        );
        $phpErrors = $e->getErrors();

        sort($goldenMessages);
        sort($phpErrors);

        $this->assertSame($goldenMessages, $phpErrors, "Mismatch between golden file and PHP binding for $goldenPath");
    }
}
