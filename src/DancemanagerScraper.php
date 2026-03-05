<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

class DancemanagerScraper
{
    protected string $baseUrl = 'https://dancemanager.ru';

    public function __construct(private readonly Client $client)
    {
    }

    /**
     * @return list<TournamentDto>
     * @throws GuzzleException
     */
    public function getTournaments(): array
    {
        $tournamentsArrays = $this->fetchTournaments();

        return array_map(
            static fn (array $data): TournamentDto => TournamentDto::fromArray($data),
            $tournamentsArrays
        );
    }

    /**
     * @return list<array{title: string, date: mixed, date_end: mixed, link: non-falsy-string, city: ?string, organizer: ?string}>
     * @throws GuzzleException
     */
    private function fetchTournaments(): array
    {
        $tournaments = [];

        $url = $this->baseUrl;
        $response = $this->client->get($url);
        $html = $response->getBody()->getContents();
        $crawler = new Crawler($html);

        $events = $crawler->filter('div[id^="event_"]');

        foreach ($events as $eventDiv) {
            $eventNode = new Crawler($eventDiv);
            /** @var string $eventId */
            $eventId = $eventNode->attr('id');
            $guid = str_replace('event_', '', $eventId);

            $title = trim($eventNode->text());
            $link = $this->baseUrl . '/competitions?guid=' . $guid;

            $information = $eventNode->nextAll()->eq(0)->text();

            $info = $this->splitLocationAndName($information);

            $dates = $this->extractDatesFromCompetitionPage($link);

            $tournaments[] = [
                'title' => $title,
                'date' => $dates['start'] ?? 'N/A',
                'date_end' => $dates['end'] ?? null,
                'link' => $link,
                'city' => $info['city'] !== '' ? $info['city'] : null,
                'organizer' => $info['organizer'] !== '' ? $info['organizer'] : null,
            ];
        }

        $nextPageExists = $crawler->filter('li.page-item a.page-link:contains("»")')->count() > 0;

        if ($nextPageExists) {
            $nextPageElement = $crawler->filter('li.page-item a.page-link:contains("»")')->first();
            $nextPageHref = $nextPageElement->attr('href');

            if (is_string($nextPageHref) && preg_match('/page(\d+)=([2-9]\d*)/', $nextPageHref, $matches)) {
                $pageNum = $matches[2];
                $pageParam = $matches[1];

                while ($pageNum <= 10) {
                    $paginatedUrl = $this->baseUrl . '/?page' . $pageParam . '=' . $pageNum;

                    $response = $this->client->get($paginatedUrl);
                    $html = $response->getBody()->getContents();
                    $crawler = new Crawler($html);

                    $events = $crawler->filter('div[id^="event_"]');

                    if ($events->count() === 0) {
                        break;
                    }

                    foreach ($events as $eventDiv) {
                        $eventNode = new Crawler($eventDiv);
                        $eventId = $eventNode->attr('id');
                        if ($eventId === null) {
                            continue;
                        }
                        $guid = str_replace('event_', '', $eventId);

                        $title = trim($eventNode->text());
                        $link = $this->baseUrl . '/competitions?guid=' . $guid;

                        $information = $eventNode->nextAll()->eq(0)->text();

                        $info = $this->splitLocationAndName($information);

                        $dates = $this->extractDatesFromCompetitionPage($link);

                        $tournaments[] = [
                            'title' => $title,
                            'date' => $dates['start'] ?? 'N/A',
                            'date_end' => $dates['end'] ?? null,
                            'link' => $link,
                            'city' => $info['city'] !== '' ? $info['city'] : null,
                            'organizer' => $info['organizer'] !== '' ? $info['organizer'] : null,
                        ];
                    }

                    $nextPageExists = $crawler->filter('li.page-item a.page-link:contains("»")')->count() > 0;
                    if (!$nextPageExists) {
                        break;
                    }

                    $pageNum++;
                }
            }
        }

        $uniqueTournaments = [];
        $seenGuids = [];

        foreach ($tournaments as $tournament) {
            $query = parse_url($tournament['link'], PHP_URL_QUERY);
            if (!is_string($query)) {
                continue;
            }
            $guid = basename($query);
            $guid = str_replace('guid=', '', $guid);

            if (!isset($seenGuids[$guid])) {
                $seenGuids[$guid] = true;
                $uniqueTournaments[] = $tournament;
            }
        }

        usort($uniqueTournaments, static function ($a, $b) {
            if ($a['date'] !== 'N/A' && $b['date'] !== 'N/A') {
                return strtotime($a['date']) - strtotime($b['date']);
            }

            if ($a['date'] !== 'N/A') {
                return -1;
            }

            if ($b['date'] !== 'N/A') {
                return 1;
            }

            return strcmp($a['title'], $b['title']);
        });

        return $uniqueTournaments;
    }

    /**
     * @param string $url
     * @return array{start: string|null, end: string|null}
     * @throws GuzzleException
     */
    public function extractDatesFromCompetitionPage(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            $content = $crawler->filter('body')->html();

            $monthMap = [
                'января' => '01',
                'февраля' => '02',
                'марта' => '03',
                'апреля' => '04',
                'мая' => '05',
                'июня' => '06',
                'июля' => '07',
                'августа' => '08',
                'сентября' => '09',
                'октября' => '10',
                'ноября' => '11',
                'декабря' => '12',
            ];


            $twoDatesPattern = '/\b(0?[1-9]|[12][0-9]|3[01])[\.\/\-](0?[1-9]|1[0-2])[\.\/\-](\d{4})\s*<br>\s*(0?[1-9]|[12][0-9]|3[01])[\.\/\-](0?[1-9]|1[0-2])[\.\/\-](\d{4})\b/i';
            if (preg_match($twoDatesPattern, $content, $matches)) {
                return [
                    'start' => sprintf('%02d.%02d.%s', (int)$matches[1], (int)$matches[2], $matches[3]),
                    'end' => sprintf('%02d.%02d.%s', (int)$matches[4], (int)$matches[5], $matches[6]),
                ];
            }

            $dmyPattern = '/\b(0?[1-9]|[12][0-9]|3[01])[\.\/\-](0?[1-9]|1[0-2])[\.\/\-](\d{4})\b/';
            if (preg_match($dmyPattern, $content, $matches)) {
                $date = sprintf('%02d.%02d.%s', (int)$matches[1], (int)$matches[2], $matches[3]);
                return ['start' => $date, 'end' => null];
            }

            $dayMonthYearPattern = '/\b(0?[1-9]|[12][0-9]|3[01])\s+(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)\s+(\d{4})\b/i';
            if (preg_match($dayMonthYearPattern, $content, $matches)) {
                $month = $monthMap[mb_strtolower($matches[2])] ?? '01';
                $date = sprintf('%02d.%02d.%s', (int)$matches[1], (int)$month, $matches[3]);
                return ['start' => $date, 'end' => null];
            }

            return ['start' => null, 'end' => null];
        } catch (\Exception $e) {
            return ['start' => null, 'end' => null];
        }
    }

    /**
     * @param string $input
     * @return array{city: string, organizer: string}
     */
    public function splitLocationAndName(string $input): array
    {
        $parts = explode(',', $input, 2);

        return [
            'city' => trim($parts[0]),
            'organizer' => isset($parts[1]) ? trim($parts[1]) : '',
        ];
    }
}
