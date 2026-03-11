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
        $client = $this->createStub(Client::class);
        $scraper = new DancemanagerScraper($client);
        $result = $scraper->splitLocationAndName('Москва, Организатор');

        self::assertSame('Москва', $result['city']);
        self::assertSame('Организатор', $result['organizer']);
    }

    public function testSplitLocationAndNameWithOnlyCity(): void
    {
        $client = $this->createStub(Client::class);
        $scraper = new DancemanagerScraper($client);
        $result = $scraper->splitLocationAndName('Санкт-Петербург');

        self::assertSame('Санкт-Петербург', $result['city']);
        self::assertSame('', $result['organizer']);
    }

    public function testSplitLocationAndNameWithEmptyString(): void
    {
        $client = $this->createStub(Client::class);
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
        $client = $this->createStub(Client::class);
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
        $client = $this->createStub(Client::class);
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
        $client = $this->createStub(Client::class);
        $client->method('get')->willReturn(new Response(200, [], $html));

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->extractDatesFromCompetitionPage('https://example.com');

        self::assertNotEmpty($result['start']);
    }

    /**
     * @throws GuzzleException
     */
    public function testExtractDatesFromCompetitionPageWithNoDates(): void
    {
        $html = '<body>Контент без дат</body>';
        $client = $this->createStub(Client::class);
        $client->method('get')->willReturn(new Response(200, [], $html));

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->extractDatesFromCompetitionPage('https://example.com');

        self::assertNull($result['start']);
        self::assertNull($result['end']);
    }

    /**
     * @throws GuzzleException
     */
    public function testExtractDatesFromCompetitionPageWithDmyFormat(): void
    {
        $html = '<body>Дата: 05-12-2024</body>';
        $client = $this->createStub(Client::class);
        $client->method('get')->willReturn(new Response(200, [], $html));

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->extractDatesFromCompetitionPage('https://example.com');

        self::assertSame('05.12.2024', $result['start']);
    }

    /**
     * @throws GuzzleException
     */
    public function testExtractDatesFromCompetitionPageWithAllRussianMonths(): void
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
            $html = "<body>Конкурс 20 $month 2024</body>";
            $client = $this->createStub(Client::class);
            $client->method('get')->willReturn(new Response(200, [], $html));

            $scraper = new DancemanagerScraper($client);
            $result = $scraper->extractDatesFromCompetitionPage('https://example.com');

            self::assertSame("20.$expected.2024", $result['start'], "Failed for month: $month");
        }
    }

    public function testSplitLocationAndNameWithMultipleCommas(): void
    {
        $client = $this->createStub(Client::class);
        $scraper = new DancemanagerScraper($client);
        $result = $scraper->splitLocationAndName('Москва, Организатор, Дополнительно');

        self::assertSame('Москва', $result['city']);
        self::assertSame('Организатор, Дополнительно', $result['organizer']);
    }

    public function testSplitLocationAndNameWithWhitespace(): void
    {
        $client = $this->createStub(Client::class);
        $scraper = new DancemanagerScraper($client);
        $result = $scraper->splitLocationAndName('  Москва  ,  Организатор  ');

        self::assertSame('Москва', $result['city']);
        self::assertSame('Организатор', $result['organizer']);
    }

    public function testSplitLocationAndNameWithOnlyComma(): void
    {
        $client = $this->createStub(Client::class);
        $scraper = new DancemanagerScraper($client);
        $result = $scraper->splitLocationAndName(',');

        self::assertSame('', $result['city']);
        self::assertSame('', $result['organizer']);
    }

    /**
     * @throws GuzzleException
     */
    public function testGetTournamentsReturnsEmptyArrayWhenNoEvents(): void
    {
        $mainPageHtml = '<html><body></body></html>';

        $client = $this->createMock(Client::class);
        $client->expects(self::once())
            ->method('get')
            ->with('https://dancemanager.ru')
            ->willReturn(new Response(200, [], $mainPageHtml));

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->getTournaments();

        self::assertEmpty($result);
    }

    /**
     * @throws GuzzleException
     */
    public function testGetTournamentsParsesSingleEvent(): void
    {
        $mainPageHtml = <<<'HTML'
<html><body>
<div id="event_abc123">Турнир по танцам</div>
<div>Москва, Организатор ООО</div>
</body></html>
HTML;

        $competitionPageHtml = '<body>Дата: 15.03.2024</body>';

        $client = $this->createMock(Client::class);
        $client->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(static function (string $url) use ($mainPageHtml, $competitionPageHtml) {
                if ($url === 'https://dancemanager.ru') {
                    return new Response(200, [], $mainPageHtml);
                }
                if (str_contains($url, '/competitions?guid=')) {
                    return new Response(200, [], $competitionPageHtml);
                }
                throw new \RuntimeException("Unexpected URL: $url");
            });

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->getTournaments();

        self::assertCount(1, $result);
        self::assertSame('Турнир по танцам', $result[0]->getTitle());
        self::assertSame('15.03.2024', $result[0]->getDate());
        self::assertSame('Москва', $result[0]->getCity());
        self::assertSame('Организатор ООО', $result[0]->getOrganizer());
    }

    /**
     * @throws GuzzleException
     */
    public function testGetTournamentsRemovesDuplicatesByGuid(): void
    {
        $mainPageHtml = <<<'HTML'
<html><body>
<div id="event_abc123">Турнир 1</div>
<div>Москва</div>
<div id="event_abc123">Турнир 1 (дубликат)</div>
<div>Санкт-Петербург</div>
</body></html>
HTML;

        $competitionPageHtml = '<body>20.04.2024</body>';

        $client = $this->createMock(Client::class);
        $client->expects(self::atLeast(2))
            ->method('get')
            ->willReturnCallback(static function (string $url) use ($mainPageHtml, $competitionPageHtml) {
                if ($url === 'https://dancemanager.ru') {
                    return new Response(200, [], $mainPageHtml);
                }
                return new Response(200, [], $competitionPageHtml);
            });

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->getTournaments();

        self::assertCount(1, $result);
    }

    /**
     * @throws GuzzleException
     */
    public function testGetTournamentsSortsByDate(): void
    {
        $mainPageHtml = <<<'HTML'
<html><body>
<div id="event_zzz">Турнир позже</div>
<div>Москва</div>
<div id="event_aaa">Турнир раньше</div>
<div>Санкт-Петербург</div>
</body></html>
HTML;

        $client = $this->createMock(Client::class);
        $client->expects(self::exactly(3))
            ->method('get')
            ->willReturnCallback(static function (string $url) use ($mainPageHtml) {
                if (str_contains($url, '/competitions?guid=zzz')) {
                    return new Response(200, [], '<body>15.06.2024</body>');
                }
                if (str_contains($url, '/competitions?guid=aaa')) {
                    return new Response(200, [], '<body>10.06.2024</body>');
                }
                return new Response(200, [], $mainPageHtml);
            });

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->getTournaments();

        self::assertCount(2, $result);
        self::assertSame('Турнир раньше', $result[0]->getTitle());
        self::assertSame('Турнир позже', $result[1]->getTitle());
    }

    /**
     * @throws GuzzleException
     */
    public function testGetTournamentsHandlesNullEventId(): void
    {
        $mainPageHtml = <<<'HTML'
<html><body>
<div>Без ID</div>
<div>Москва</div>
</body></html>
HTML;

        $client = $this->createMock(Client::class);
        $client->expects(self::once())
            ->method('get')
            ->with('https://dancemanager.ru')
            ->willReturn(new Response(200, [], $mainPageHtml));

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->getTournaments();

        self::assertEmpty($result);
    }

    /**
     * @throws GuzzleException
     */
    public function testGetTournamentsWithNullDateFallback(): void
    {
        $mainPageHtml = <<<'HTML'
<html><body>
<div id="event_test">Турнир без даты</div>
<div>Москва</div>
</body></html>
HTML;

        $client = $this->createMock(Client::class);
        $client->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(static function (string $url) use ($mainPageHtml) {
                if ($url === 'https://dancemanager.ru') {
                    return new Response(200, [], $mainPageHtml);
                }
                return new Response(200, [], '<body>Нет даты</body>');
            });

        $scraper = new DancemanagerScraper($client);
        $result = $scraper->getTournaments();

        self::assertCount(1, $result);
        self::assertSame('N/A', $result[0]->getDate());
    }
}
