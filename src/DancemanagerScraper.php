<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Simtel\DanceManagerScraper\Http\PageFetcherInterface;
use Simtel\DanceManagerScraper\Interface\TournamentScraperInterface;
use Simtel\DanceManagerScraper\Parser\DateParser;
use Simtel\DanceManagerScraper\Parser\TournamentListParser;

class DancemanagerScraper implements TournamentScraperInterface
{
    private const MAX_PAGES = 10;

    public function __construct(
        private readonly PageFetcherInterface $fetcher,
        private readonly DateParser $dateParser = new DateParser(),
        private readonly TournamentListParser $listParser = new TournamentListParser(),
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly string $baseUrl = 'https://dancemanager.ru',
    ) {
    }

    /**
     * @return list<TournamentDto>
     */
    public function getTournaments(): array
    {
        $events = $this->collectEvents();

        $tournaments = $this->buildTournaments($events);

        usort($tournaments, self::sortByDate(...));

        $this->logger->info('Total tournaments found: ' . count($tournaments));

        return $tournaments;
    }

    /**
     * @return array<string, array{guid: string, title: string, city: string|null, organizer: string|null}>
     */
    private function collectEvents(): array
    {
        $events = [];

        $listHtml = $this->fetcher->fetchHtml($this->baseUrl);
        $this->mergeEvents($events, $listHtml);

        $nextPage = $this->listParser->nextPageInfo($listHtml);

        if ($nextPage === null) {
            return $events;
        }

        $pageNum = $nextPage['pageNum'];
        $pageParam = $nextPage['pageParam'];

        while ($pageNum <= self::MAX_PAGES) {
            $url = $this->baseUrl . '/?page' . $pageParam . '=' . $pageNum;
            $this->logger->info("Fetching page: $url");

            $html = $this->fetcher->fetchHtml($url);
            $this->mergeEvents($events, $html);

            if (!$this->listParser->hasNextPage($html)) {
                break;
            }

            $pageNum++;
        }

        return $events;
    }

    /**
     * @param array<string, array{guid: string, title: string, city: string|null, organizer: string|null}> $events
     */
    private function mergeEvents(array &$events, string $html): void
    {
        foreach ($this->listParser->parseEvents($html) as $event) {
            $events[$event['guid']] = $event;
        }
    }

    /**
     * @param array<string, array{guid: string, title: string, city: string|null, organizer: string|null}> $events
     * @return list<TournamentDto>
     */
    private function buildTournaments(array $events): array
    {
        if ($events === []) {
            return [];
        }

        $urlByGuid = [];
        foreach ($events as $guid => $event) {
            $urlByGuid[$guid] = $this->competitionUrl($guid);
        }

        $pages = $this->fetcher->fetchHtmlPool(array_values($urlByGuid));
        $idx = 0;

        $tournaments = [];
        foreach ($events as $guid => $event) {
            $dates = $this->dateParser->parse($pages[$idx]);
            $idx++;

            $tournaments[] = new TournamentDto(
                title: $event['title'],
                date: $dates['start'],
                dateEnd: $dates['end'],
                link: $urlByGuid[$guid],
                city: $event['city'],
                organizer: $event['organizer'],
            );
        }

        return $tournaments;
    }

    private function competitionUrl(string $guid): string
    {
        return $this->baseUrl . '/competitions?guid=' . $guid;
    }

    private static function sortByDate(TournamentDto $a, TournamentDto $b): int
    {
        $dateA = $a->getDate();
        $dateB = $b->getDate();

        if ($dateA instanceof DateTimeImmutable && $dateB instanceof DateTimeImmutable) {
            return $dateA <=> $dateB;
        }

        if ($dateA instanceof DateTimeImmutable) {
            return -1;
        }

        if ($dateB instanceof DateTimeImmutable) {
            return 1;
        }

        return strcmp($a->getTitle(), $b->getTitle());
    }
}
