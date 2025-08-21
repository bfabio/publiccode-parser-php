<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser\Tests;

use Bfabio\PublicCodeParser\Exception\ParserException;
use Bfabio\PublicCodeParser\Exception\ValidationException;
use Bfabio\PublicCodeParser\Parser;
use Bfabio\PublicCodeParser\ParserConfig;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
	private Parser $parser;
	private string $yaml;

	protected function setUp(): void
	{
		$this->parser = new Parser();
		$this->yaml = file_get_contents(__DIR__ . "/fixtures/valid.minimal.yml");
	}

	public function testPublicCodeAccessors(): void
	{
		$publicCode = $this->parser->parse($this->yaml);

		$this->assertEquals('Medusa', $publicCode->getName());
		$this->assertEquals('AGPL-3.0-or-later', $publicCode->getLicense());
		$this->assertEquals(['web'], $publicCode->getPlatforms());

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
