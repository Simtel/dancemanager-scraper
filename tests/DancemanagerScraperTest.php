<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use Simtel\DanceManagerScraper\DancemanagerScraper;
use Simtel\DanceManagerScraper\Tests\Support\MapPageFetcher;

class DancemanagerScraperTest extends BaseTestCase
{
    public function testGetTournamentsReturnsEmptyArrayWhenNoEvents(): void
    {
        $mainPageHtml = '<html><body></body></html>';

        $scraper = new DancemanagerScraper(new MapPageFetcher([
            'https://dancemanager.ru' => $mainPageHtml,
        ]));

        self::assertEmpty($scraper->getTournaments());
    }

    public function testGetTournamentsParsesSingleEvent(): void
    {
        $mainPageHtml = <<<'HTML'
<html><body>
<div id="event_abc123">Турнир по танцам</div>
<div>Москва, Организатор ООО</div>
</body></html>
HTML;

        $competitionPageHtml = '<body>Дата: 15.03.2024</body>';

        $scraper = new DancemanagerScraper(new MapPageFetcher([
            'https://dancemanager.ru' => $mainPageHtml,
            'https://dancemanager.ru/competitions?guid=abc123' => $competitionPageHtml,
        ]));

        $result = $scraper->getTournaments();

        self::assertCount(1, $result);
        self::assertSame('Турнир по танцам', $result[0]->getTitle());
        self::assertSame('15.03.2024', $result[0]->getDate()?->format('d.m.Y'));
        self::assertSame('Москва', $result[0]->getCity());
        self::assertSame('Организатор ООО', $result[0]->getOrganizer());
    }

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

        $scraper = new DancemanagerScraper(new MapPageFetcher([
            'https://dancemanager.ru' => $mainPageHtml,
            'https://dancemanager.ru/competitions?guid=abc123' => $competitionPageHtml,
        ]));

        $result = $scraper->getTournaments();

        self::assertCount(1, $result);
    }

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

        $scraper = new DancemanagerScraper(new MapPageFetcher([
            'https://dancemanager.ru' => $mainPageHtml,
            'https://dancemanager.ru/competitions?guid=zzz' => '<body>15.06.2024</body>',
            'https://dancemanager.ru/competitions?guid=aaa' => '<body>10.06.2024</body>',
        ]));

        $result = $scraper->getTournaments();

        self::assertCount(2, $result);
        self::assertSame('Турнир раньше', $result[0]->getTitle());
        self::assertSame('Турнир позже', $result[1]->getTitle());
    }

    public function testGetTournamentsHandlesNullEventId(): void
    {
        $mainPageHtml = <<<'HTML'
<html><body>
<div>Без ID</div>
<div>Москва</div>
</body></html>
HTML;

        $scraper = new DancemanagerScraper(new MapPageFetcher([
            'https://dancemanager.ru' => $mainPageHtml,
        ]));

        self::assertEmpty($scraper->getTournaments());
    }

    public function testGetTournamentsWithNullDateFallback(): void
    {
        $mainPageHtml = <<<'HTML'
<html><body>
<div id="event_test">Турнир без даты</div>
<div>Москва</div>
</body></html>
HTML;

        $scraper = new DancemanagerScraper(new MapPageFetcher([
            'https://dancemanager.ru' => $mainPageHtml,
            'https://dancemanager.ru/competitions?guid=test' => '<body>Нет даты</body>',
        ]));

        $result = $scraper->getTournaments();

        self::assertCount(1, $result);
        self::assertNull($result[0]->getDate());
    }

    public function testGetTournamentsParsesMultiplePages(): void
    {
        $mainPageHtml = <<<'HTML'
<html><body>
<div id="event_abc123">Турнир по танцам</div>
<div>Москва, Организатор ООО</div>
<li class="page-item"><a class="page-link" href="https://dancemanager.ru/?page1=2">»</a></li>
</body></html>
HTML;

        $secondPageHtml = <<<'HTML'
<html><body>
<div id="event_abc321">Турнир по танцам Динамо</div>
<div>Красногорск, Организатор ООО</div>
</body></html>
HTML;

        $competitionPageHtml = '<body>Дата: 15.03.2024</body>';

        $scraper = new DancemanagerScraper(new MapPageFetcher([
            'https://dancemanager.ru' => $mainPageHtml,
            'https://dancemanager.ru/?page1=2' => $secondPageHtml,
            'https://dancemanager.ru/competitions?guid=abc123' => $competitionPageHtml,
            'https://dancemanager.ru/competitions?guid=abc321' => $competitionPageHtml,
        ]));

        $result = $scraper->getTournaments();

        self::assertCount(2, $result);
        self::assertSame('Турнир по танцам', $result[0]->getTitle());
        self::assertSame('15.03.2024', $result[0]->getDate()?->format('d.m.Y'));
        self::assertSame('Москва', $result[0]->getCity());
        self::assertSame('Организатор ООО', $result[0]->getOrganizer());
        self::assertSame('Турнир по танцам Динамо', $result[1]->getTitle());
        self::assertSame('15.03.2024', $result[1]->getDate()?->format('d.m.Y'));
        self::assertSame('Красногорск', $result[1]->getCity());
        self::assertSame('Организатор ООО', $result[1]->getOrganizer());
    }
}
