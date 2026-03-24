<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Simtel\DanceManagerScraper\Interface\TournamentGroupScraperInterface;
use Symfony\Component\DomCrawler\Crawler;

class TournamentGroupScrapper implements TournamentGroupScraperInterface
{
    private LoggerInterface $logger;

    private string $baseUrl = 'https://dancemanager.ru';

    private string $partUrlPath = '/part?eventGuid=%s&partGuid=%s&isShowUnconfirmed=1';

    /**
     * @param Client $client
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private readonly Client $client,
        ?LoggerInterface $logger = null,
        ?string $baseUrl = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        if ($baseUrl !== null) {
            $this->baseUrl = $baseUrl;
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }


    /**
     * @param TournamentDto $tournament
     * @return TournamentGroupDto[]
     * @throws GuzzleException
     */
    public function getGroups(TournamentDto $tournament): array
    {
        $response = $this->client->get($tournament->getLink());
        $html = $response->getBody()->getContents();
        $crawler = new Crawler($html);


        $parts = $crawler->filter('a[data-partguid]');

        $this->logger->info('Найдено отделений:' . $parts->count());
        $allGroups = [];
        foreach ($parts as $part) {
            $partNode = new Crawler($part);
            /** @var string $partGuid */
            $partGuid = $partNode->attr('data-partguid');
            $this->logger->info('Получение данных для ' . trim($partNode->text()) . ' (partId:' . $partGuid . ')');
            $groups = $this->scrapePart($this->getPartUrl($tournament->getGuid(), $partGuid));
            $allGroups = array_merge($allGroups, $groups);
        }

        return $allGroups;
    }

    private function getPartUrl(string $eventGuid, string $partGuid): string
    {
        return $this->baseUrl . sprintf($this->partUrlPath, $eventGuid, $partGuid);
    }

    /**
     * @return TournamentGroupDto[]
     * @throws GuzzleException
     */
    private function scrapePart(string $url): array
    {
        $response = $this->client->get($url);
        $html = $response->getBody()->getContents();
        $crawler = new Crawler($html);

        $groups = $crawler->filter('a[data-competitionguid]');
        $this->logger->info('Найдено групп:' . $groups->count());
        $outGroups = [];
        foreach ($groups as $group) {
            $registrations = 0;
            $groupNode = new Crawler($group);
            $text = $groupNode->text();
            $textGroup = explode('.', $text, 2);
            $this->logger->info('Группа: ' . $text);
            if (preg_match('/(\d+)$/', $text, $matches)) {
                $registrations = (int)$matches[1];
            }

            $groupName = (string)($textGroup[1] ?? '');
            /** @var string $name */
            $name = ($groupName !== '') ? trim((string)preg_replace('/\d+$/', '', $groupName)) : '';
            $outGroups[] = new TournamentGroupDto(
                (int)$textGroup[0],
                $name,
                $registrations
            );
        }

        return $outGroups;
    }

}
