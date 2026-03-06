<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;
use Simtel\DanceManagerScraper\TournamentDto;
use Simtel\DanceManagerScraper\TournamentGroupDto;
use Simtel\DanceManagerScraper\TournamentGroupScrapper;

class TournamentGroupScrapperTest extends BaseTestCase
{
    private TournamentGroupScrapper $scrapper;

    protected function setUp(): void
    {
        parent::setUp();
        $client = $this->createStub(Client::class);
        $this->scrapper = new TournamentGroupScrapper($client, new NullLogger());
    }

    public function testCreateInstance(): void
    {
        self::assertInstanceOf(TournamentGroupScrapper::class, $this->scrapper);
    }

    /**
     * @throws GuzzleException
     */
    public function testGetGroupsWithEmptyResponse(): void
    {
        $html = '<html><body>No groups</body></html>';
        $client = $this->createStub(Client::class);
        $client->method('get')->willReturn(new Response(200, [], $html));

        $scrapper = new TournamentGroupScrapper($client, new NullLogger());
        $tournament =  $tournament = new TournamentDto(
            'Tournament',
            '2026-03-01',
            '2026-03-01',
            'https://example.com/competitions?guid=123',
            'Moscow',
            'Organizer'
        );

        $result = $scrapper->getGroups($tournament);

        self::assertEmpty($result);
    }

    /**
     * @throws GuzzleException
     */
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

        $client = $this->createStub(Client::class);

        $client
            ->method('get')
            ->willReturnCallback(static function (string $url) use ($tournamentPageHtml, $partPageHtml) {
                if (str_contains($url, 'competitions?guid=')) {
                    return new Response(200, [], $tournamentPageHtml);
                }

                return new Response(200, [], $partPageHtml);
            });

        $scrapper = new TournamentGroupScrapper($client, new NullLogger());
        $tournament = new TournamentDto(
            'Tournament',
            '2026-03-01',
            '2026-03-01',
            'https://example.com/competitions?guid=123',
            'Moscow',
            'Organizer'
        );

        $result = $scrapper->getGroups($tournament);

        self::assertCount(4, $result);
        self::assertInstanceOf(TournamentGroupDto::class, $result[0]);
        self::assertSame(1, $result[0]->getNumber());
        self::assertSame('Юниоры 1', $result[0]->getName());
        self::assertSame(25, $result[0]->getRegistrations());
    }

    public function testSetLogger(): void
    {
        $logger = new NullLogger();
        $this->scrapper->setLogger($logger);

        self::assertInstanceOf(TournamentGroupScrapper::class, $this->scrapper);
    }
}
