<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests\Support;

use Simtel\DanceManagerScraper\Exception\HttpFetchException;
use Simtel\DanceManagerScraper\Http\PageFetcherInterface;

final class MapPageFetcher implements PageFetcherInterface
{
    /**
     * @param array<string, string> $pages url => html
     */
    public function __construct(private readonly array $pages)
    {
    }

    public function fetchHtml(string $url): string
    {
        return $this->pages[$url]
            ?? throw new HttpFetchException('Unexpected URL: ' . $url);
    }

    public function fetchHtmlPool(array $urls): array
    {
        $result = [];

        foreach ($urls as $url) {
            $result[] = $this->fetchHtml($url);
        }

        return $result;
    }
}
