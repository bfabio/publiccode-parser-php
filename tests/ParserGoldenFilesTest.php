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
use Bfabio\PublicCodeParser\ValidationError;
use Bfabio\PublicCodeParser\ValidationWarning;
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
    public function testGenerateGoldenValidWithWarnings(string $yamlPath): void
    {
        $goldenPath = $this->generateGolden($yamlPath);

        $publicCode = $this->parser->parseFile($yamlPath);

        $this->assertValidationResultsMatchGolden(
            $publicCode->getWarnings(),
            $publicCode->getValidationWarnings(),
            $goldenPath,
        );
    }

    /** @dataProvider validFilesWithWarningsNoNetworkProvider */
    public function testGenerateGoldenValidWithWarningsNoNetwork(string $yamlPath): void
    {
        $goldenPath = $this->generateGolden($yamlPath, true);

        $publicCode = $this->parserNoNetwork->parseFile($yamlPath);

        $this->assertValidationResultsMatchGolden(
            $publicCode->getWarnings(),
            $publicCode->getValidationWarnings(),
            $goldenPath,
        );
    }

    /** @dataProvider invalidFilesProvider */
    public function testGenerateGoldenInvalid(string $yamlPath): void
    {
        $goldenPath = $this->generateGolden($yamlPath);

        try {
            $this->parser->parseFile($yamlPath);

            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertValidationResultsMatchGolden(
                array_merge($e->getErrors(), $e->getWarnings()),
                array_merge($e->getValidationErrors(), $e->getValidationWarnings()),
                $goldenPath,
            );
        }
    }

    /** @dataProvider invalidFilesNoNetworkProvider */
    public function testGenerateGoldenInvalidNoNetwork(string $yamlPath): void
    {
        $goldenPath = $this->generateGolden($yamlPath, true);

        try {
            $this->parserNoNetwork->parseFile($yamlPath);

            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertValidationResultsMatchGolden(
                array_merge($e->getErrors(), $e->getWarnings()),
                array_merge($e->getValidationErrors(), $e->getValidationWarnings()),
                $goldenPath,
            );
        }
    }

    /** @return non-empty-array<string, array{string}> */
    public static function validFilesWithWarningsProvider(): array
    {
        // no 'valid/' directory here because those files don't have any errors or warnings
        return self::scanTestdata(['valid_with_warnings']);
    }

    /** @return non-empty-array<string, array{string}> */
    public static function validFilesWithWarningsNoNetworkProvider(): array
    {
        // no 'valid/no-network' directory here because those files don't have any errors or warnings
        return self::scanTestdata(['valid/no-network', 'valid_with_warnings/no-network']);
    }

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

        // Exit code 1 means the file has validation errors, the JSON
        // output is still complete.
        if ($exitCode !== 0 && $exitCode !== 1) {
            throw new \RuntimeException("publiccode-parser failed on $yamlPath (exit $exitCode)");
        }

        return $outFile;
    }

    /**
     * @param list<string> $messages
     * @param list<ValidationError|ValidationWarning> $validationResults
     */
    private function assertValidationResultsMatchGolden(
        array $messages,
        array $validationResults,
        string $goldenPath,
    ): void {
        $content = file_get_contents($goldenPath);
        if ($content === false) {
            throw new \RuntimeException("Cannot read file: $goldenPath");
        }

        $golden = json_decode($content, true);

        $goldenMessages = array_map(
            fn (array $g) =>
            sprintf(
                'publiccode.yml:%d:%d: %s: %s%s',
                $g['line'],
                $g['column'],
                $g['type'],
                $g['key'] !== '' ? $g['key'] . ': ' : '',
                $g['description'],
            ),
            $golden,
        );
        $goldenValidationResults = $this->normalizeGoldenResults($golden);
        $phpValidationResults = $this->normalizeValidationResults($validationResults);

        sort($goldenMessages);
        sort($messages);

        $this->assertSame(
            $goldenMessages,
            $messages,
            "Mismatch between golden file and PHP binding for $goldenPath",
        );
        $this->assertSame(
            $goldenValidationResults,
            $phpValidationResults,
            "Mismatch between structured validation results and golden file for $goldenPath",
        );
    }

    /**
     * @param list<array<string, mixed>> $golden
     * @return list<array{string, int, int, string, string}>
     */
    private function normalizeGoldenResults(array $golden): array
    {
        $issues = array_map(
            fn (array $issue) => [
                (string) $issue['type'],
                (int) $issue['line'],
                (int) $issue['column'],
                (string) $issue['key'],
                (string) $issue['description'],
            ],
            $golden,
        );
        sort($issues);

        return $issues;
    }

    /**
     * @param list<ValidationError|ValidationWarning> $issues
     * @return list<array{string, int, int, string, string}>
     */
    private function normalizeValidationResults(array $issues): array
    {
        $normalized = array_map(
            fn (ValidationError | ValidationWarning $issue) => [
                $issue->getType(),
                $issue->getLine(),
                $issue->getColumn(),
                $issue->getKey(),
                $issue->getDescription(),
            ],
            $issues,
        );
        sort($normalized);

        return $normalized;
    }
}
