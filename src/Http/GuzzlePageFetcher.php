<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Simtel\DanceManagerScraper\Exception\HttpFetchException;

final class GuzzlePageFetcher implements PageFetcherInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly ?CacheItemPoolInterface $cache = null,
        private readonly int $cacheTtl = 3600,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function fetchHtml(string $url): string
    {
        return $this->fetchHtmlPool([$url])[0];
    }

    public function fetchHtmlPool(array $urls): array
    {
        $result = [];
        $toFetch = [];

        foreach ($urls as $index => $url) {
            $cached = $this->readCache($url);

            if ($cached !== null) {
                $result[$index] = $cached;
            } else {
                $toFetch[$index] = $url;
            }
        }

        if ($toFetch !== []) {
            $fetched = $this->doFetch($toFetch);

            foreach ($fetched as $index => $html) {
                $result[$index] = $html;
                $this->writeCache($toFetch[$index], $html);
            }
        }

        ksort($result);

        return array_values($result);
    }

    /**
     * @param array<int, string> $urls
     * @return array<int, string>
     * @throws HttpFetchException
     */
    private function doFetch(array $urls): array
    {
        $promises = [];

        foreach ($urls as $index => $url) {
            $promises[$index] = $this->client->getAsync($url)->then(
                static fn ($response): string => (string) $response->getBody()
            );
        }

        $settled = Utils::settle($promises)->wait();

        $result = [];
        $failures = [];

        foreach ($settled as $index => $entry) {
            if ($entry['state'] === 'fulfilled') {
                $result[$index] = $entry['value'];
            } else {
                $failures[] = $urls[$index];
                $this->logger->error(
                    sprintf('Failed to fetch %s: %s', $urls[$index], $this->describeReason($entry['reason']))
                );
            }
        }

        if ($failures !== []) {
            throw new HttpFetchException('Failed to fetch pages: ' . implode(', ', $failures));
        }

        return $result;
    }

    private function describeReason(mixed $reason): string
    {
        if ($reason instanceof \Throwable) {
            return $reason->getMessage();
        }

        return var_export($reason, true);
    }

    private function readCache(string $url): ?string
    {
        if ($this->cache === null) {
            return null;
        }

        $item = $this->cache->getItem($this->cacheKey($url));

        if (!$item->isHit()) {
            return null;
        }

        $value = $item->get();

        return is_string($value) ? $value : null;
    }

    private function writeCache(string $url, string $html): void
    {
        if ($this->cache === null) {
            return;
        }

        try {
            $item = $this->cache->getItem($this->cacheKey($url));
            $item->set($html)->expiresAfter($this->cacheTtl);
            $this->cache->save($item);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to cache page: ' . $e->getMessage());
        }
    }

    private function cacheKey(string $url): string
    {
        return 'dancemanager:page:' . hash('sha256', $url);
    }
}
