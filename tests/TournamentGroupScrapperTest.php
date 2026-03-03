<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Simtel\DanceManagerScraper\Tournament;
use Simtel\DanceManagerScraper\TournamentGroupDto;
use Simtel\DanceManagerScraper\TournamentGroupScrapper;

class TournamentGroupScrapperTest extends BaseTestCase
{
    private TournamentGroupScrapper $scrapper;

    protected function setUp(): void
    {
        parent::setUp();
        $client = $this->createMock(Client::class);
        $this->scrapper = new TournamentGroupScrapper($client, new NullLogger());
    }

    public function testCreateInstance(): void
    {
        self::assertInstanceOf(TournamentGroupScrapper::class, $this->scrapper);
    }

    public function testGetGroupsWithEmptyResponse(): void
    {
        $html = '<html><body>No groups</body></html>';
        $client = $this->createMock(Client::class);
        $client->method('get')->willReturn(new Response(200, [], $html));

        $scrapper = new TournamentGroupScrapper($client, new NullLogger());
        $tournament = new Tournament('https://example.com?guid=123', '123');

        $result = $scrapper->getGroups($tournament);

        self::assertEmpty($result);
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

        $client = self::getMockBuilder(Client::class)
            ->onlyMethods(['get'])
            ->getMock();

        $client->expects(self::exactly(3))
            ->method('get')
            ->willReturnCallback(function (string $url) use ($tournamentPageHtml, $partPageHtml) {
                if (str_contains($url, 'competitions?guid=')) {
                    return new Response(200, [], $tournamentPageHtml);
                }

                return new Response(200, [], $partPageHtml);
            });

        $scrapper = new TournamentGroupScrapper($client, new NullLogger());
        $tournament = new Tournament('https://example.com/competitions?guid=123', '123');

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
