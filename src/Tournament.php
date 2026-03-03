<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper;

readonly class Tournament
{
    public function __construct(private string $link, private string $guid)
    {
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

}
