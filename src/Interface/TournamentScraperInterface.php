<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Interface;

use Simtel\DanceManagerScraper\TournamentDto;

interface TournamentScraperInterface
{
    /**
     * @return list<TournamentDto>
     */
    public function getTournaments(): array;
}
