<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser\Tests;

use Bfabio\PublicCodeParser\Exception\ParserException;
use Bfabio\PublicCodeParser\Exception\ValidationException;
use Bfabio\PublicCodeParser\Parser;
use Bfabio\PublicCodeParser\ParserConfig;
use Bfabio\PublicCodeParser\PublicCodeV0;
use Bfabio\PublicCodeParser\ValidationWarning;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private Parser $parser;
    private string $yaml;

    protected function setUp(): void
    {
        $path = __DIR__ . '/fixtures/valid.yml';

        $this->parser = new Parser();

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Cannot read fixture: $path");
        }

        $this->yaml = $content;
    }

    public function testPublicCodeType(): void
    {
        $publicCode = $this->parser->parse($this->yaml);

        $this->assertInstanceOf(PublicCodeV0::class, $publicCode);
    }

    public function testPublicCodeAccessors(): void
    {
        $publicCode = $this->parser->parse($this->yaml);

        $this->assertEquals('0', $publicCode->getPubliccodeYmlVersion());
        $this->assertEquals('Medusa', $publicCode->getName());
        $this->assertEquals('mySuite', $publicCode->getApplicationSuite());
        $this->assertEquals('https://github.com/italia/developers.italia.it.git', $publicCode->getUrl());
        $this->assertEquals(null, $publicCode->getLandingUrl());
        $this->assertEquals('0.10.11', $publicCode->getSoftwareVersion());
        $this->assertEquals(null, $publicCode->getLogo());
        $this->assertEquals('AGPL-3.0-or-later', $publicCode->getLicense());
        $this->assertEquals(['web'], $publicCode->getPlatforms());
        $this->assertEquals(null, $publicCode->getRoadmap());

        $this->assertNotNull($publicCode->getDescription('en-GB'));
        $this->assertNull($publicCode->getDescription('it'));

        $maintenance = $publicCode->getMaintenance();
        $this->assertEquals('community', $maintenance['type']);

        $categories = $publicCode->getCategories();
        $this->assertNotNull($categories);
        $this->assertContains('cloud-management', $categories);
    }

    public function testToArray(): void
    {
        $publicCode = $this->parser->parse($this->yaml);

        $array = $publicCode->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('url', $array);
    }

    public function testToJson(): void
    {
        $publicCode = $this->parser->parse($this->yaml);

        $json = $publicCode->toJson();
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('Medusa', $decoded['name']);
    }

    public function testWarnings(): void
    {
        $config = new ParserConfig();
        $config->setDisableNetwork(true);
        $parser = new Parser($config);

        $publicCode = $parser->parseFile(
            __DIR__ . '/fixtures/testdata/v0/valid_with_warnings/unicode_grapheme_clusters.yml',
        );

        $this->assertSame(
            [
                'publiccode.yml:23:5: warning: description.en.genericName: ' .
                "This key is DEPRECATED and will be removed in the future. It's safe to drop it",
            ],
            $publicCode->getWarnings(),
        );

        $validationWarnings = $publicCode->getValidationWarnings();
        $this->assertCount(1, $validationWarnings);
        $this->assertInstanceOf(ValidationWarning::class, $validationWarnings[0]);
        $this->assertSame('warning', $validationWarnings[0]->getType());
        $this->assertSame(23, $validationWarnings[0]->getLine());
        $this->assertSame(5, $validationWarnings[0]->getColumn());
        $this->assertSame('description.en.genericName', $validationWarnings[0]->getKey());
        $this->assertSame(
            "This key is DEPRECATED and will be removed in the future. It's safe to drop it",
            $validationWarnings[0]->getDescription(),
        );
    }

    public function testStructuredValidationErrorsPreserveLegacyErrors(): void
    {
        $config = new ParserConfig();
        $config->setDisableNetwork(true);
        $parser = new Parser($config);

        try {
            $parser->parseFile(
                __DIR__ . '/fixtures/testdata/v0/invalid/applicationSuite_wrong_type.yml',
            );
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['publiccode.yml:4:1: error: applicationSuite: wrong type for this field'],
                $exception->getErrors(),
            );

            $validationErrors = $exception->getValidationErrors();
            $this->assertCount(1, $validationErrors);
            $this->assertSame('error', $validationErrors[0]->getType());
            $this->assertSame(4, $validationErrors[0]->getLine());
            $this->assertSame(1, $validationErrors[0]->getColumn());
            $this->assertSame('applicationSuite', $validationErrors[0]->getKey());
            $this->assertSame('wrong type for this field', $validationErrors[0]->getDescription());
            $this->assertSame($exception->getErrors()[0], (string) $validationErrors[0]);
        }
    }

    public function testNonValidationErrorsRemainParserExceptions(): void
    {
        try {
            $this->parser->parseFile(__DIR__ . '/fixtures/does-not-exist.yml');
            $this->fail('Expected ParserException was not thrown');
        } catch (ValidationException $exception) {
            $this->fail('Expected a non-validation ParserException, got: ' . $exception->getMessage());
        } catch (ParserException $exception) {
            $this->assertStringContainsString("can't open file", $exception->getMessage());
        }
    }
}
