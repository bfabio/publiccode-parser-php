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

        static::assertSame('0', $publicCode->getPubliccodeYmlVersion());
        static::assertSame('Medusa', $publicCode->getName());
        static::assertSame('mySuite', $publicCode->getApplicationSuite());
        static::assertSame('https://github.com/italia/developers.italia.it.git', $publicCode->getUrl());
        static::assertSame(null, $publicCode->getLandingUrl());
        static::assertSame('0.10.11', $publicCode->getSoftwareVersion());
        static::assertSame(null, $publicCode->getLogo());
        static::assertSame('AGPL-3.0-or-later', $publicCode->getLicense());
        static::assertSame(['web'], $publicCode->getPlatforms());
        static::assertSame(null, $publicCode->getRoadmap());

        static::assertNotNull($publicCode->getDescription('en_GB'));
        static::assertNull($publicCode->getDescription('it'));

        $maintenance = $publicCode->getMaintenance();
        static::assertSame('community', $maintenance['type']);

        $categories = $publicCode->getCategories();
        static::assertContains('cloud-management', $categories);
    }

    public function testToArray(): void
    {
        $publicCode = $this->parser->parse($this->yaml);

        $array = $publicCode->toArray();
        static::assertIsArray($array);
        static::assertArrayHasKey('name', $array);
        static::assertArrayHasKey('url', $array);
    }

    public function testToJson(): void
    {
        $publicCode = $this->parser->parse($this->yaml);

        $json = $publicCode->toJson();
        static::assertJson($json);

        $decoded = json_decode($json, true);
        static::assertSame('Medusa', $decoded['name']);
    }
}
