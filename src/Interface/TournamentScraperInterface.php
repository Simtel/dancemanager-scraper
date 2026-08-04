<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Interface;

use Simtel\DanceManagerScraper\Exception\HttpFetchException;
use Simtel\DanceManagerScraper\TournamentDto;

interface TournamentScraperInterface
{
    /**
     * @return list<TournamentDto>
     * @throws HttpFetchException
     */
    public function getTournaments(): array;
}
