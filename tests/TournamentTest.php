<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use Simtel\DanceManagerScraper\TournamentDto;

class TournamentTest extends BaseTestCase
{
    public function testCreateWithValidData(): void
    {
        $tournament = new TournamentDto(
            'Tournament',
            '2026-03-01',
            '2026-03-01',
            'https://example.com/competitions?guid=123',
            'Moscow',
            'Organizer'
        );

        self::assertSame('https://example.com/competitions?guid=123', $tournament->getLink());
        self::assertSame('123', $tournament->getGuid());
    }

    public function testGetLinkReturnsCorrectValue(): void
    {
        $link = 'https://dancemanager.ru/competitions?guid=abc123';
        $tournament = new TournamentDto(
            'Tournament',
            '2026-03-01',
            '2026-03-01',
            $link,
            'Moscow',
            'Organizer'
        );

        self::assertEquals($link, $tournament->getLink());
    }

    public function testGetGuidReturnsCorrectValue(): void
    {
        $guid = 'test-guid-456';
        $tournament = new TournamentDto(
            'Tournament',
            '2026-03-01',
            '2026-03-01',
            'https://example.com?guid=' . $guid,
            'Moscow',
            'Organizer'
        );

        self::assertEquals($guid, $tournament->getGuid());
    }
}
