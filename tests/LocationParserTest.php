<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use Simtel\DanceManagerScraper\Parser\LocationParser;

class LocationParserTest extends BaseTestCase
{
    private LocationParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new LocationParser();
    }

    public function testWithBothValues(): void
    {
        $result = $this->parser->split('Москва, Организатор');

        self::assertSame('Москва', $result['city']);
        self::assertSame('Организатор', $result['organizer']);
    }

    public function testWithOnlyCity(): void
    {
        $result = $this->parser->split('Санкт-Петербург');

        self::assertSame('Санкт-Петербург', $result['city']);
        self::assertSame('', $result['organizer']);
    }

    public function testWithEmptyString(): void
    {
        $result = $this->parser->split('');

        self::assertSame('', $result['city']);
        self::assertSame('', $result['organizer']);
    }

    public function testWithMultipleCommas(): void
    {
        $result = $this->parser->split('Москва, Организатор, Дополнительно');

        self::assertSame('Москва', $result['city']);
        self::assertSame('Организатор, Дополнительно', $result['organizer']);
    }

    public function testWithWhitespace(): void
    {
        $result = $this->parser->split('  Москва  ,  Организатор  ');

        self::assertSame('Москва', $result['city']);
        self::assertSame('Организатор', $result['organizer']);
    }

    public function testWithOnlyComma(): void
    {
        $result = $this->parser->split(',');

        self::assertSame('', $result['city']);
        self::assertSame('', $result['organizer']);
    }
}
