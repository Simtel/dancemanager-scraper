<?php

declare(strict_types=1);

namespace Simtel\DanceManagerScraper\Tests;

use Simtel\DanceManagerScraper\TournamentGroupDto;

class TournamentGroupDtoTest extends BaseTestCase
{
    public function testCreateWithValidData(): void
    {
        $dto = new TournamentGroupDto(1, 'Юниоры 1', 15);

        self::assertSame(1, $dto->getNumber());
        self::assertSame('Юниоры 1', $dto->getName());
        self::assertSame(15, $dto->getRegistrations());
    }

    public function testGetNumberReturnsCorrectValue(): void
    {
        $dto = new TournamentGroupDto(5, 'Молодежь', 20);

        self::assertSame(5, $dto->getNumber());
    }

    public function testGetNameReturnsCorrectValue(): void
    {
        $dto = new TournamentGroupDto(1, 'Взрослые', 10);

        self::assertEquals('Взрослые', $dto->getName());
    }

    public function testGetRegistrationsReturnsCorrectValue(): void
    {
        $dto = new TournamentGroupDto(1, 'Тест', 0);

        self::assertSame(0, $dto->getRegistrations());
    }
}
