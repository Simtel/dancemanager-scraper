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
use Simtel\DanceManagerScraper\DancemanagerScraper;
use Simtel\DanceManagerScraper\TournamentGroupScrapper;

$client = new Client();

// Получение списка турниров
$scraper = new DancemanagerScraper($client);
$tournaments = $scraper->getTournaments();

foreach ($tournaments as $tournament) {
    echo $tournament->getTitle() . ' - ' . $tournament->getDate() . "\n";
    echo '  Город: ' . $tournament->getCity() . "\n";
    echo '  Организатор: ' . $tournament->getOrganizer() . "\n";
}

// Получение групп турнира
$groupScraper = new TournamentGroupScrapper($client);

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
make install
```

### Запуск тестов

```bash
./vendor/bin/phpunit
```

### Статический анализ (PHPStan)

```bash
make phpstan
```

### Форматирование кода (Pint)

```bash
make pint
```

## Лицензия

MIT