<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use DateTimeImmutable;
use Simtel\DanceManagerScraper\TournamentDto;

class TournamentTest extends BaseTestCase
{
    public function testCreateWithValidData(): void
    {
        $tournament = new TournamentDto(
            'Tournament',
            new DateTimeImmutable('2026-03-01'),
            new DateTimeImmutable('2026-03-01'),
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
            null,
            null,
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
            null,
            null,
            'https://example.com?guid=' . $guid,
            'Moscow',
            'Organizer'
        );

        self::assertEquals($guid, $tournament->getGuid());
    }
}
