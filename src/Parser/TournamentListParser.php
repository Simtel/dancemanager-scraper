<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Parser;

use Symfony\Component\DomCrawler\Crawler;

final class TournamentListParser
{
    private LocationParser $locationParser;

    public function __construct(?LocationParser $locationParser = null)
    {
        $this->locationParser = $locationParser ?? new LocationParser();
    }

    /**
     * @return list<array{guid: string, title: string, city: string|null, organizer: string|null}>
     */
    public function parseEvents(string $html): array
    {
        $crawler = new Crawler($html);
        $events = [];

        foreach ($crawler->filter('div[id^="event_"]') as $eventDiv) {
            $eventNode = new Crawler($eventDiv);
            $eventId = $eventNode->attr('id');

            if ($eventId === null) {
                continue;
            }

            $information = $eventNode->nextAll()->eq(0)->text();
            $info = $this->locationParser->split($information);

            $events[] = [
                'guid' => str_replace('event_', '', $eventId),
                'title' => trim($eventNode->text()),
                'city' => $info['city'] !== '' ? $info['city'] : null,
                'organizer' => $info['organizer'] !== '' ? $info['organizer'] : null,
            ];
        }

        return $events;
    }

    public function hasNextPage(string $html): bool
    {
        return (new Crawler($html))->filter('li.page-item a.page-link:contains("»")')->count() > 0;
    }

    /**
     * @return array{pageParam: string, pageNum: int}|null
     */
    public function nextPageInfo(string $html): ?array
    {
        $nextPage = (new Crawler($html))->filter('li.page-item a.page-link:contains("»")');

        if ($nextPage->count() === 0) {
            return null;
        }

        $href = $nextPage->first()->attr('href');

        if (!is_string($href)) {
            return null;
        }

        if (preg_match('/page(\d+)=([2-9]\d*)/', $href, $matches) !== 1) {
            return null;
        }

        return [
            'pageParam' => $matches[1],
            'pageNum' => (int) $matches[2],
        ];
    }
}
