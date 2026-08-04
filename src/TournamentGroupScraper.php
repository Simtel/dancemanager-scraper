<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Simtel\DanceManagerScraper\Http\PageFetcherInterface;
use Simtel\DanceManagerScraper\Interface\TournamentGroupScraperInterface;
use Symfony\Component\DomCrawler\Crawler;

class TournamentGroupScraper implements TournamentGroupScraperInterface
{
    private const PART_URL_PATH = '/part?eventGuid=%s&partGuid=%s&isShowUnconfirmed=1';

    public function __construct(
        private readonly PageFetcherInterface $fetcher,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly string $baseUrl = 'https://dancemanager.ru',
    ) {
    }

    /**
     * @return list<TournamentGroupDto>
     */
    public function getGroups(TournamentDto $tournament): array
    {
        $tournamentHtml = $this->fetcher->fetchHtml($tournament->getLink());
        $partGuids = $this->parsePartGuids($tournamentHtml);

        $this->logger->info('Найдено отделений: ' . count($partGuids));

        if ($partGuids === []) {
            return [];
        }

        $urlByGuid = [];
        foreach ($partGuids as $partGuid) {
            $urlByGuid[$partGuid] = $this->getPartUrl($tournament->getGuid(), $partGuid);
        }

        $pages = $this->fetcher->fetchHtmlPool(array_values($urlByGuid));

        $allGroups = [];
        $index = 0;
        foreach ($partGuids as $partGuid) {
            $allGroups = array_merge($allGroups, $this->scrapePart($pages[$index]));
            $index++;
        }

        return $allGroups;
    }

    /**
     * @return list<string>
     */
    private function parsePartGuids(string $html): array
    {
        $crawler = new Crawler($html);
        $partGuids = [];

        foreach ($crawler->filter('a[data-partguid]') as $part) {
            $partNode = new Crawler($part);
            $partGuid = $partNode->attr('data-partguid');

            if ($partGuid === null) {
                continue;
            }

            $this->logger->info('Получение данных для ' . trim($partNode->text()) . ' (partId:' . $partGuid . ')');
            $partGuids[] = $partGuid;
        }

        return $partGuids;
    }

    /**
     * @return list<TournamentGroupDto>
     */
    private function scrapePart(string $html): array
    {
        $crawler = new Crawler($html);
        $groups = $crawler->filter('a[data-competitionguid]');

        $this->logger->info('Найдено групп: ' . $groups->count());

        $outGroups = [];
        foreach ($groups as $group) {
            $groupNode = new Crawler($group);
            $text = $groupNode->text();

            $this->logger->info('Группа: ' . $text);

            $registrations = $this->extractRegistrations($text);
            $name = $this->extractGroupName($text);

            $outGroups[] = new TournamentGroupDto(
                (int) explode('.', $text, 2)[0],
                $name,
                $registrations,
            );
        }

        return $outGroups;
    }

    private function extractRegistrations(string $text): int
    {
        if (preg_match('/(\d+)$/', $text, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }

    private function extractGroupName(string $text): string
    {
        $parts = explode('.', $text, 2);
        $groupName = $parts[1] ?? '';

        return ($groupName !== '')
            ? trim((string) preg_replace('/\d+$/', '', $groupName))
            : '';
    }

    private function getPartUrl(string $eventGuid, string $partGuid): string
    {
        return $this->baseUrl . sprintf(self::PART_URL_PATH, $eventGuid, $partGuid);
    }
}
