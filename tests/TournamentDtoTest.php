<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use Simtel\DanceManagerScraper\TournamentDto;

class TournamentDtoTest extends BaseTestCase
{
    public function testCreateTournamentDto(): void
    {
        $dto = new TournamentDto(
            title: 'World Cup 2025',
            date: '2025-06-10',
            dateEnd: '2025-06-12',
            link: 'https://example.com/tournament/123',
            city: 'Berlin',
            organizer: 'Dancemanager'
        );

        $this->assertSame('World Cup 2025', $dto->getTitle());
        $this->assertSame('2025-06-10', $dto->getDate());
        $this->assertSame('2025-06-12', $dto->getDateEnd());
        $this->assertSame('Berlin', $dto->getCity());
        $this->assertSame('https://example.com/tournament/123', $dto->getLink());
        $this->assertSame('Dancemanager', $dto->getOrganizer());
    }


    public function testToArray(): void
    {
        $dto = new TournamentDto(
            title: 'Euro Dance 2025',
            date: '2025-07-05',
            dateEnd: '2025-07-07',
            link: 'https://example.com/eurodance',
            city: 'Rome',
            organizer: 'Dancemanager'
        );

        $expected = [
            'title' => 'Euro Dance 2025',
            'date' => '2025-07-05',
            'date_end' => '2025-07-07',
            'link' => 'https://example.com/eurodance',
            'city' => 'Rome',
            'organizer' => 'Dancemanager',
        ];

        $this->assertSame($expected, $dto->toArray());
    }



    public function testEquality(): void
    {
        $dto1 = new TournamentDto(
            title: 'Same Event',
            date: '2025-08-01',
            dateEnd: '2025-08-03',
            link: 'https://example.com/same',
            city: 'Warsaw',
            organizer: 'Dancemanager'
        );

        $dto2 = new TournamentDto(
            title: 'Same Event',
            date: '2025-08-01',
            dateEnd: '2025-08-03',
            link: 'https://example.com/same',
            city: 'Warsaw',
            organizer: 'Dancemanager'
        );

        $this->assertEquals($dto1->toArray(), $dto2->toArray());
    }

    public function testFromArray(): void
    {
        $data = [
            'title' => 'World Cup 2025',
            'date' => '2025-06-10',
            'date_end' => '2025-06-12',
            'link' => 'https://example.com/tournament/123',
            'city' => 'Berlin',
            'organizer' => 'Dance Org',
        ];

        $dto = TournamentDto::fromArray($data);

        $this->assertInstanceOf(TournamentDto::class, $dto);
        $this->assertSame('World Cup 2025', $dto->getTitle());
        $this->assertSame('2025-06-10', $dto->getDate());
        $this->assertSame('2025-06-12', $dto->getDateEnd());
        $this->assertSame('https://example.com/tournament/123', $dto->getLink());
        $this->assertSame('Berlin', $dto->getCity());
        $this->assertSame('Dance Org', $dto->getOrganizer());
    }
}
