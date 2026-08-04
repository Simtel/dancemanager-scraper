<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Parser;

final class LocationParser
{
    /**
     * Splits "City, Organizer" into city and organizer, keeping the rest as the organizer.
     *
     * @return array{city: string, organizer: string}
     */
    public function split(string $input): array
    {
        $parts = explode(',', $input, 2);

        return [
            'city' => trim($parts[0]),
            'organizer' => isset($parts[1]) ? trim($parts[1]) : '',
        ];
    }
}
