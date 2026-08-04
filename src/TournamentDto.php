<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper;

use DateTimeImmutable;

readonly class TournamentDto
{
    public function __construct(
        private string $title,
        private ?DateTimeImmutable $date,
        private ?DateTimeImmutable $dateEnd,
        private string $link,
        private ?string $city,
        private ?string $organizer,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function getDateEnd(): ?DateTimeImmutable
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
     * @return array{title: string, date: ?string, date_end: ?string, link: string, city: ?string, organizer: ?string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'date' => $this->date?->format('Y-m-d'),
            'date_end' => $this->dateEnd?->format('Y-m-d'),
            'link' => $this->link,
            'city' => $this->city,
            'organizer' => $this->organizer,
        ];
    }

    /**
     * @param array{title: string, date: \DateTimeImmutable|string|null, date_end: \DateTimeImmutable|string|null, link: string, city?: ?string, organizer?: ?string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            date: self::normalizeDate($data['date'] ?? null),
            dateEnd: self::normalizeDate($data['date_end'] ?? null),
            link: $data['link'],
            city: $data['city'] ?? null,
            organizer: $data['organizer'] ?? null,
        );
    }

    private static function normalizeDate(\DateTimeImmutable|string|null $date): ?DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable || $date === null) {
            return $date;
        }

        return new DateTimeImmutable($date);
    }
}
