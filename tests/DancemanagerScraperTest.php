<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Simtel\DanceManagerScraper\DancemanagerScraper;

class DancemanagerScraperTest extends BaseTestCase
{
    public function testSplitLocationAndNameWithBothValues(): void
    {
        $client = $this->createMock(Client::class);
        $scraper = new DancemanagerScraper($client);
        $result = $scraper->splitLocationAndName('Москва, Организатор');

        self::assertSame('Москва', $result['city']);
        self::assertSame('Организатор', $result['organizer']);
    }

    public function testSplitLocationAndNameWithOnlyCity(): void
    {
        $client = $this->createMock(Client::class);
        $scraper = new DancemanagerScraper($client);
        $result = $scraper->splitLocationAndName('Санкт-Петербург');

        self::assertSame('Санкт-Петербург', $result['city']);
        self::assertSame('', $result['organizer']);
    }

    public function testSplitLocationAndNameWithEmptyString(): void
    {
        $client = $this->createMock(Client::class);
        $scraper = new DancemanagerScraper($client);
        $result = $scraper->splitLocationAndName('');

        self::assertSame('', $result['city']);
        self::assertSame('', $result['organizer']);
    }

    /**
     * @throws GuzzleException
     */
    public function testExtractDatesFromCompetitionPageWithTwoDates(): void
    {
        $html = "<body>15.02.2024<br>\n17.02.2024</body>";
        $client = $this->createMock(Client::class);
        $client->method('get')->willReturn(new Response(200, [], $html));

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->extractDatesFromCompetitionPage('https://example.com');

        self::assertNotEmpty($result['start']);
        self::assertNotEmpty($result['end']);
    }

    /**
     * @throws GuzzleException
     */
    public function testExtractDatesFromCompetitionPageWithSingleDate(): void
    {
        $html = '<body>Контент с датой 25.12.2024</body>';
        $client = $this->createMock(Client::class);
        $client->method('get')->willReturn(new Response(200, [], $html));

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->extractDatesFromCompetitionPage('https://example.com');

        self::assertNotEmpty($result['start']);
    }

    /**
     * @throws GuzzleException
     */
    public function testExtractDatesFromCompetitionPageWithRussianMonth(): void
    {
        $html = '<body>Контент с датой 15 марта 2024 года</body>';
        $client = $this->createMock(Client::class);
        $client->method('get')->willReturn(new Response(200, [], $html));

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->extractDatesFromCompetitionPage('https://example.com');

        self::assertNotEmpty($result['start']);
    }
}
