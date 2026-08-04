<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Parser;

final class DateParser
{
    private const MONTHS = [
        'января' => 1,
        'февраля' => 2,
        'марта' => 3,
        'апреля' => 4,
        'мая' => 5,
        'июня' => 6,
        'июля' => 7,
        'августа' => 8,
        'сентября' => 9,
        'октября' => 10,
        'ноября' => 11,
        'декабря' => 12,
    ];

    private const TWO_DATES_PATTERN = '/' .
        '\b(0?[1-9]|[12][0-9]|3[01])[\.\/\-](0?[1-9]|1[0-2])[\.\/\-](\d{4})' .
        '\s*<br>\s*' .
        '(0?[1-9]|[12][0-9]|3[01])[\.\/\-](0?[1-9]|1[0-2])[\.\/\-](\d{4})\b' .
        '/i';

    private const SINGLE_DMY_PATTERN = '/\b(0?[1-9]|[12][0-9]|3[01])[\.\/\-](0?[1-9]|1[0-2])[\.\/\-](\d{4})\b/';

    private const DAY_MONTH_WORD_YEAR_PATTERN = '/' .
        '\b(0?[1-9]|[12][0-9]|3[01])\s+' .
        '(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)\s+(\d{4})\b' .
        '/i';

    /**
     * @return array{start: \DateTimeImmutable|null, end: \DateTimeImmutable|null}
     */
    public function parse(string $html): array
    {
        if (preg_match(self::TWO_DATES_PATTERN, $html, $matches) === 1) {
            return [
                'start' => $this->makeDate((int) $matches[1], (int) $matches[2], (int) $matches[3]),
                'end' => $this->makeDate((int) $matches[4], (int) $matches[5], (int) $matches[6]),
            ];
        }

        if (preg_match(self::SINGLE_DMY_PATTERN, $html, $matches) === 1) {
            return [
                'start' => $this->makeDate((int) $matches[1], (int) $matches[2], (int) $matches[3]),
                'end' => null,
            ];
        }

        if (preg_match(self::DAY_MONTH_WORD_YEAR_PATTERN, $html, $matches) === 1) {
            $month = self::MONTHS[mb_strtolower($matches[2])] ?? 1;

            return [
                'start' => $this->makeDate((int) $matches[1], $month, (int) $matches[3]),
                'end' => null,
            ];
        }

        return ['start' => null, 'end' => null];
    }

    private function makeDate(int $day, int $month, int $year): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }
}
