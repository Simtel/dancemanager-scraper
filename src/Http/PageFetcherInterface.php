<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Http;

use Simtel\DanceManagerScraper\Exception\HttpFetchException;

interface PageFetcherInterface
{
    /**
     * @throws HttpFetchException
     */
    public function fetchHtml(string $url): string;

    /**
     * @param list<string> $urls
     * @return list<string> html in the same order as $urls
     * @throws HttpFetchException
     */
    public function fetchHtmlPool(array $urls): array;
}
