<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Interface;

use Simtel\DanceManagerScraper\Exception\HttpFetchException;
use Simtel\DanceManagerScraper\TournamentDto;
use Simtel\DanceManagerScraper\TournamentGroupDto;

interface TournamentGroupScraperInterface
{
    /**
     * @param TournamentDto $tournament
     * @return list<TournamentGroupDto>
     * @throws HttpFetchException
     */
    public function getGroups(TournamentDto $tournament): array;
}
