<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Interface;

use Simtel\DanceManagerScraper\TournamentDto;
use Simtel\DanceManagerScraper\TournamentGroupDto;

interface TournamentGroupScraperInterface
{
    /**
     * @param TournamentDto $tournament
     * @return list<TournamentGroupDto>
     */
    public function getGroups(TournamentDto $tournament): array;
}
