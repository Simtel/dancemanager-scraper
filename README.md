# DanceManager Scraper

Скрепер для получения информации о турнирах и группах с сайта [dancemanager.ru](https://dancemanager.ru).

## Возможности

- Получение списка турниров с датами, городами и организаторами
- Получение списка групп для каждого турнира
- Пагинация по страницам турниров

## Требования

- PHP 8.5+
- Composer

## Установка

### В качестве зависимости в свой проект

```bash
composer require simtel/dancemanager-scraper
```

### Для разработки

```bash
composer install
```

## Использование

```php
use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Simtel\DanceManagerScraper\DancemanagerScraper;
use Simtel\DanceManagerScraper\Http\GuzzlePageFetcher;
use Simtel\DanceManagerScraper\TournamentGroupScraper;

$client = new Client();

// Кэш — опционально. Без него каждая страница будет скачиваться заново.
$cache = new FilesystemAdapter('dancemanager', 3600, __DIR__ . '/var/cache');
$fetcher = new GuzzlePageFetcher($client, $cache);

// Получение списка турниров
$scraper = new DancemanagerScraper($fetcher);
$tournaments = $scraper->getTournaments();

foreach ($tournaments as $tournament) {
    echo $tournament->getTitle() . ' - ' . ($tournament->getDate()?->format('d.m.Y') ?? 'N/A') . "\n";
    echo '  Город: ' . $tournament->getCity() . "\n";
    echo '  Организатор: ' . $tournament->getOrganizer() . "\n";
}

// Получение групп турнира
$groupScraper = new TournamentGroupScraper($fetcher);

foreach ($tournaments as $tournament) {
    $groups = $groupScraper->getGroups($tournament);

    foreach ($groups as $group) {
        echo $group->getName() . ': ' . $group->getRegistrations() . ' участников' . "\n";
    }
}
```

## Разработка

### Установка зависимостей

```bash
composer run post-install
```

### Запуск тестов

```bash
composer test
```

### Запуск тестов с покрытием

```bash
composer test:coverage
```

### Статический анализ (PHPStan)

```bash
composer phpstan
```

### Форматирование кода (Pint)

```bash
composer pint
```

### Запуск линтеров (phpstan + pint)

```bash
composer lint
```

### Запуск всех проверок (тесты + линтеры)

```bash
composer check
```

## Лицензия

MIT