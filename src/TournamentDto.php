<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper;

readonly class TournamentDto
{
    public function __construct(
        private string $title,
        private string $date,
        private ?string $dateEnd,
        private string $link,
        private ?string $city,
        private ?string $organizer,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getDateEnd(): ?string
    {
        return $this->dateEnd;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getOrganizer(): ?string
    {
        return $this->organizer;
    }

    public function getGuid(): string
    {
        $query = parse_url($this->link, PHP_URL_QUERY);
        if (!is_string($query)) {
            throw new \InvalidArgumentException('Query is not a string');
        }
        $params = [];
        parse_str($query, $params);
        /** @var string|null $guid */
        $guid = $params['guid'] ?? null;
        if ($guid === null) {
            throw new \InvalidArgumentException('Guid is not find in query');
        }
        return $guid;
    }

    /**
     * @return array{title: string, date: string, date_end: ?string, link: string, city: ?string, organizer: ?string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'date' => $this->date,
            'date_end' => $this->dateEnd,
            'link' => $this->link,
            'city' => $this->city,
            'organizer' => $this->organizer,
        ];
    }

    /**
     * @param array{title: string, date: string, date_end: ?string, link: non-falsy-string, city: ?string, organizer: ?string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            date: $data['date'],
            dateEnd: $data['date_end'],
            link: $data['link'],
            city: $data['city'],
            organizer: $data['organizer'],
        );
    }

}
