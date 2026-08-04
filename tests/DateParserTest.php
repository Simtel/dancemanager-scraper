<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use Simtel\DanceManagerScraper\Parser\DateParser;

class DateParserTest extends BaseTestCase
{
    private DateParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DateParser();
    }

    public function testParsesTwoDates(): void
    {
        $html = '<body>15.02.2024<br>' . "\n" . '17.02.2024</body>';

        $result = $this->parser->parse($html);

        self::assertNotNull($result['start']);
        self::assertNotNull($result['end']);
        self::assertSame('15.02.2024', $result['start']->format('d.m.Y'));
        self::assertSame('17.02.2024', $result['end']->format('d.m.Y'));
    }

    public function testParsesSingleDate(): void
    {
        $result = $this->parser->parse('<body>Контент с датой 25.12.2024</body>');

        $start = $result['start'];
        self::assertNotNull($start);
        self::assertNull($result['end']);
        self::assertSame('25.12.2024', $start->format('d.m.Y'));
    }

    public function testParsesRussianMonth(): void
    {
        $result = $this->parser->parse('<body>Контент с датой 15 марта 2024 года</body>');

        self::assertNotNull($result['start']);
        self::assertSame('15.03.2024', $result['start']->format('d.m.Y'));
    }

    public function testReturnsNullsWhenNoDates(): void
    {
        $result = $this->parser->parse('<body>Контент без дат</body>');

        self::assertNull($result['start']);
        self::assertNull($result['end']);
    }

    public function testParsesDmyFormat(): void
    {
        $result = $this->parser->parse('<body>Дата: 05-12-2024</body>');

        $start = $result['start'];
        self::assertNotNull($start);
        self::assertSame('05.12.2024', $start->format('d.m.Y'));
    }

    public function testParsesAllRussianMonths(): void
    {
        $months = [
            'января' => '01',
            'февраля' => '02',
            'марта' => '03',
            'апреля' => '04',
            'мая' => '05',
            'июня' => '06',
            'июля' => '07',
            'августа' => '08',
            'сентября' => '09',
            'октября' => '10',
            'ноября' => '11',
            'декабря' => '12',
        ];

        foreach ($months as $month => $expected) {
            $result = $this->parser->parse("<body>Конкурс 20 $month 2024</body>");

            $start = $result['start'];
            self::assertNotNull($start);
            self::assertSame("20.$expected.2024", $start->format('d.m.Y'), "Failed for month: $month");
        }
    }
}
