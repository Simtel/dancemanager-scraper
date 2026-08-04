<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use Simtel\DanceManagerScraper\Tests\Support\MapPageFetcher;
use Simtel\DanceManagerScraper\TournamentDto;
use Simtel\DanceManagerScraper\TournamentGroupDto;
use Simtel\DanceManagerScraper\TournamentGroupScraper;

class TournamentGroupScraperTest extends BaseTestCase
{
    public function testCreateInstance(): void
    {
        $scraper = new TournamentGroupScraper(new MapPageFetcher([]));

        self::assertInstanceOf(TournamentGroupScraper::class, $scraper);
    }

    public function testGetGroupsWithEmptyResponse(): void
    {
        $tournament = $this->makeTournament();
        $scraper = new TournamentGroupScraper(new MapPageFetcher([
            'https://example.com/competitions?guid=123' => '<html><body>No groups</body></html>',
        ]));

        self::assertEmpty($scraper->getGroups($tournament));
    }

    public function testGetGroupsParsesGroupsCorrectly(): void
    {
        $tournamentPageHtml = <<<'HTML'
<html>
<body>
    <a data-partguid="part1">Отделение 1</a>
    <a data-partguid="part2">Отделение 2</a>
</body>
</html>
HTML;

        $partPageHtml = <<<'HTML'
<html>
<body>
    <a data-competitionguid="g1">1. Юниоры 1 25</a>
    <a data-competitionguid="g2">2. Молодежь 10</a>
</body>
</html>
HTML;

        $tournament = $this->makeTournament();
        $scraper = new TournamentGroupScraper(new MapPageFetcher([
            'https://example.com/competitions?guid=123' => $tournamentPageHtml,
            'https://dancemanager.ru/part?eventGuid=123&partGuid=part1&isShowUnconfirmed=1' => $partPageHtml,
            'https://dancemanager.ru/part?eventGuid=123&partGuid=part2&isShowUnconfirmed=1' => $partPageHtml,
        ]));

        $result = $scraper->getGroups($tournament);

        self::assertCount(4, $result);
        self::assertInstanceOf(TournamentGroupDto::class, $result[0]);
        self::assertSame(1, $result[0]->getNumber());
        self::assertSame('Юниоры 1', $result[0]->getName());
        self::assertSame(25, $result[0]->getRegistrations());
    }

    private function makeTournament(): TournamentDto
    {
        return new TournamentDto(
            'Tournament',
            null,
            null,
            'https://example.com/competitions?guid=123',
            'Moscow',
            'Organizer',
        );
    }
}
