<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use Simtel\DanceManagerScraper\Tournament;

class TournamentTest extends BaseTestCase
{
    public function testCreateWithValidData(): void
    {
        $tournament = new Tournament('https://example.com/competitions?guid=123', '123');

        self::assertSame('https://example.com/competitions?guid=123', $tournament->getLink());
        self::assertSame('123', $tournament->getGuid());
    }

    public function testGetLinkReturnsCorrectValue(): void
    {
        $link = 'https://dancemanager.ru/competitions?guid=abc123';
        $tournament = new Tournament($link, 'abc123');

        self::assertEquals($link, $tournament->getLink());
    }

    public function testGetGuidReturnsCorrectValue(): void
    {
        $guid = 'test-guid-456';
        $tournament = new Tournament('https://example.com?guid=' . $guid, $guid);

        self::assertEquals($guid, $tournament->getGuid());
    }
}
