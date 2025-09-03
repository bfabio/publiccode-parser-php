<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser\Tests;

use Bfabio\PublicCodeParser\Parser;
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

        $this->assertNotNull($publicCode->getDescription('en_GB'));
        $this->assertNull($publicCode->getDescription('it'));

        $maintenance = $publicCode->getMaintenance();
        $this->assertEquals('community', $maintenance['type']);

        $categories = $publicCode->getCategories();
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
}
