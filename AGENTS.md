# AGENTS.md

Guidelines for working on the `dancemanager-scraper` project — a PHP web scraper extracting tournament and dance group data from dancemanager.ru.

## Project Overview

- **Language:** PHP 8.4+ (CI targets 8.5)
- **PSR Autoloading:** Root namespace `Simtel\DancemanagerScraper\` → `src/`
- **Test namespace:** `Simtel\Tests\` → `tests/`
- **Package manager:** Composer (`require` + `require-dev`)
- **CI/CD:** GitHub Actions (.github/workflows/php.yml) running PHPStan (max level), Laravel Pint (PSR-12), and PHPUnit on every push / PR.

## Directory Structure

```
├── src/                          # Source code
│   ├── DancemanagerScraper.php           # Main scraper: paginated tournament list, deduplication by GUID
│   ├── TournamentGroupScraper.php        # Group scraper: part GUIDs → child pages → dance groups with reg counts
│   ├── TournamentDto.php                 # Immutable tournament value object + toArray/fromArray
│   ├── TournamentGroupDto.php            # Immutable group value object + toArray/fromArray
│   ├── Exception/
│   │   ├── ScraperException.php          # Base exception (\RuntimeException)
│   │   └── HttpFetchException.php        # HTTP fetch failure
│   ├── Http/
│   │   ├── PageFetcherInterface.php      # Contract for fetching HTML pages
│   │   └── GuzzlePageFetcher.php         # Guzzle impl with async pool + PSR-6 cache (1h TTL)
│   ├── Interface/
│   │   ├── TournamentScraperInterface.php       # getTournaments(): array
│   │   └── TournamentGroupScraperInterface.php  # getGroups(TournamentDto): array
│   └── Parser/
│       ├── TournamentListParser.php   # Parses tournament index page (CSS selector: div[id^="event_"])
│       ├── DateParser.php             # Parses date strings (DD.MM.YYYY ranges, Russian months)
│       └── LocationParser.php         # Splits "City, Organizer" CSV string
├── tests/                        # PHPUnit test suite (~40 assertions total)
│   ├── BaseTestCase.php          # Shared test setup
│   ├── Support/MapPageFetcher.php # Deterministic HTML response double
│   ├── DancemanagerScraperTest.php
│   ├── TournamentGroupScraperTest.php
│   ├── DateParserTest.php
│   ├── LocationParserTest.php
│   ├── TournamentDtoTest.php
│   ├── TournamentGroupDtoTest.php
│   └── TournamentTest.php
```

## Core Architecture

The scraper follows a layered architecture:

1. **HTTP Layer** (`Http\*`) — Fetches raw HTML pages. `GuzzlePageFetcher` supports async concurrent requests via `fetchHtmlPool()` and optional PSR-6 caching keyed by SHA-256 of the URL.
2. **Parser Layer** (`Parser\*`) — Converts DOM elements (`Symfony\Component\DomCrawler\Crawler`) into structured data. Each parser has a single responsibility (list parsing, dates, locations).
3. **Scraper Orchestrator** (`DancemanagerScraper`, `TournamentGroupScraper`) — Coordinates HTTP fetch + parsing, handles deduplication by GUID, sorting by start date, and pagination (up to 10 pages for tournaments).
4. **DTOs** (`TournamentDto`, `TournamentGroupDto`) — Immutable readonly value objects. Use `fromArray(array)` for deserialization and `toArray()` for serialization.

## Running Quality Gates

```bash
make stan          # phpstan analyse -l max config/phpstan.neon --no-progress
make test          # ./vendor/bin/phpunit
make fix           # ./vendor/bin/pint --preset=psr12
make all           # Runs test + fix first, then runs make test again
```

Or directly via Composer scripts:

```bash
composer analyse     # PHPStan
composer test        # PHPUnit
composer format      # Laravel Pint
composer verify-test # Test → format → test round
composer verify-all  # Full CI: analyze + format + verify-test
```

## Coding Conventions

- **PHP standards:** PSR-12 formatting (enforced by Laravel Pint), PHP 8.4 syntax (readonly classes, intersection types, etc.).
- **Type declarations:** Strict typing everywhere (`declare(strict_types=1);`). Prefer explicit return types and typed parameters. DTOs are `readonly class`.
- **Interfaces vs concrete:** Public contracts go in `src/Interface/*Interface.php`; implementations in `src/*`. Tests double against interfaces via `Support/MapPageFetcher.php`.
- **Exceptions:** Hierarchy rooted at `\Simtel\DancemanagerScraper\Exception\ScraperException` (extends `\RuntimeException`). Specific errors extend it (e.g., `HttpFetchException`).
- **Naming:** PascalCase classes, camelCase methods, snake_case variables, UPPER_CASE constants. Files match class names exactly.
- **Dependencies:** Only use libraries already declared in `composer.json`. Currently: `guzzlehttp/guzzle`, `symfony/dom-crawler`, `psr/cache`, `psr/log`. Dev deps: `phpunit/phpunit`, `phpstan/phpstan`, `laravel/pint`, `symfony/cache`, `symfony/css-selector`.
- **No new dependencies** without prior discussion.
- **Tests must pass** before any submission. Run `vendor/bin/phpunit` locally at minimum. For full assurance run `make all`.
- **Static analysis must pass** (`make stan`). All nullable types should be explicitly written; prefer `array<key, Type>` notation over `array<Type>`.
- **DTOs:** Always make values set-only once constructed. Keep constructors private when using factory patterns like `fromArray()`.
- **Error handling:** Log warnings via PSR-3 logger instead of throwing for non-fatal issues (e.g., missing date/location fields).
- **HTML parsing selectors:** Use CSS selectors via Crawler (e.g., `div[class*="card"]`, `span[itemprop="name"]`). Check structure carefully as site markup may change.
- **Cache:** Default TTL is 1 hour via `CACHE_TTL = 3600`. New cache features should respect PSR-6 semantics (`get()`, `fetch()`, `save()`, `INVALIDATE`).

## Testing Notes

- All tests use `Simtel\Tests\BaseTestCase` which bootstraps PHPUnit's `\PHPUnit\Framework\TestCase`.
- The test doubles reside in `tests/Support/MapPageFetcher.php` — they load pre-written HTML responses from disk, making tests deterministic and offline.
- When testing new parsers or scrapers, add new MapPageFetcher methods and corresponding test cases covering both happy paths and edge cases.
- Existing coverage includes datetime parsing (all 12 Russian months), deduplication by GUID, sorting, empty/null handling, and group extraction by partition.
