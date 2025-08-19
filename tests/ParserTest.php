<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser\Tests;

use Bfabio\PublicCodeParser\Exception\ParserException;
use Bfabio\PublicCodeParser\Exception\ValidationException;
use Bfabio\PublicCodeParser\Parser;
use Bfabio\PublicCodeParser\ParserOptions;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testParseValidFile(): void
    {
        $yaml = $this->getValidPublicCodeYaml();
        $publicCode = $this->parser->parse($yaml);

        $this->assertEquals('Medusa', $publicCode->getName());
        $this->assertEquals('https://example.com/medusa', $publicCode->getUrl());
        $this->assertNotNull($publicCode->getDescription('en'));
    }

    public function testParseInvalidFile(): void
    {
        $this->expectException(ValidationException::class);

        $yaml = "invalid: yaml: content:";
        $this->parser->parse($yaml);
    }

    public function testParseWithOptions(): void
    {
        $options = new ParserOptions();
        $options->setDisableNetwork(true);

        $parser = new Parser($options);
        $yaml = $this->getValidPublicCodeYaml();

        $publicCode = $parser->parse($yaml);
        $this->assertNotNull($publicCode);
    }

    public function testPublicCodeAccessors(): void
    {
        $yaml = $this->getValidPublicCodeYaml();
        $publicCode = $this->parser->parse($yaml);

        $this->assertEquals('Medusa', $publicCode->getName());
        $this->assertEquals('AGPL-3.0-or-later', $publicCode->getLicense());
        $this->assertEquals(['web'], $publicCode->getPlatforms());

        $this->assertNotNull($publicCode->getDescription('en'));
        $this->assertNotNull($publicCode->getDescription('it'));

        $maintenance = $publicCode->getMaintenance();
        $this->assertEquals('internal', $maintenance['type']);

        $categories = $publicCode->getCategories();
        $this->assertContains('it-development', $categories);
    }

    public function testToArray(): void
    {
        $yaml = $this->getValidPublicCodeYaml();
        $publicCode = $this->parser->parse($yaml);

        $array = $publicCode->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('url', $array);
    }

    public function testToJson(): void
    {
        $yaml = $this->getValidPublicCodeYaml();
        $publicCode = $this->parser->parse($yaml);

        $json = $publicCode->toJson();
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('Medusa', $decoded['name']);
    }

    public function testValidate(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'publiccode');
        file_put_contents($tempFile, $this->getValidPublicCodeYaml());

        $this->assertTrue($this->parser->validate($tempFile));

        unlink($tempFile);
    }

    public function testValidateInvalid(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'publiccode');
        file_put_contents($tempFile, 'invalid: yaml:');

        $this->assertFalse($this->parser->validate($tempFile));

        unlink($tempFile);
    }

    public function testGetDependencies(): void
    {
        $yaml = $this->getValidPublicCodeYaml();
        $publicCode = $this->parser->parse($yaml);

        $deps = $publicCode->getDependencies();
        $this->assertIsArray($deps);
    }

    private function getValidPublicCodeYaml(): string
    {
        return <<<YAML
publiccodeYmlVersion: "0.2"
name: Medusa
url: "https://example.com/medusa"
landingURL: "https://example.com/medusa"
releaseDate: "2017-04-15"
developmentStatus: stable
softwareType: standalone/web
platforms:
  - web
categories:
  - it-development
maintenance:
  type: internal
  contacts:
    - name: Francesco Rossi
      email: "francesco.rossi@comune.reggioemilia.it"
legal:
  license: AGPL-3.0-or-later
  repoOwner: Comune di Reggio Emilia
description:
  it:
    shortDescription: >
      Sistema di gestione documentale
    longDescription: >
      Medusa è un sistema di gestione documentale che permette
      di organizzare e gestire i documenti digitali.
    features:
      - Gestione documenti
      - Workflow documentale
  en:
    shortDescription: >
      Document management system
    longDescription: >
      Medusa is a document management system that allows
      organizing and managing digital documents.
    features:
      - Document management
      - Document workflow
YAML;
    }
}
