<?php

declare(strict_types=1);

namespace App\Tests\Service\PersonService;

use App\Helper\TextHelper;
use App\Service\PersonService;
use PHPUnit\Framework\TestCase;

class NamesTest extends TestCase
{
    private readonly PersonService $personService;

    public function setUp(): void
    {
        parent::setUp();

        $textHelper = new TextHelper();
        $this->personService = new PersonService($textHelper);
    }

    /**
     * @dataProvider dataSameNamesProvider
     */
    public function testIsSameNames(string $nameA, string $nameB, bool $expected): void
    {
        $result = $this->personService->isSameNames($nameA, $nameB);

        $this->assertEquals($expected, $result, 'Error for names: ' . $nameA . ', ' . $nameB);
    }

    private function dataSameNamesProvider(): array
    {
        return [
            ['Смольская А.А.', 'Смольская А.А.', true],
            ['Сачко А.М.', 'Сачко М.А.', false],
            ['Яскевіч Алена Аляксандраўна', 'Яскевіч А.А.', true],
            ['Шчаглова А.Ю.', 'Шчаглов А.Ю.', false],
            ['Пашынская Г.', 'Пашынская Г.М.', true],
            ['Наважылава Надзея Алегаўна', 'Наважылава Надзея', true],
            ['Куксанава П.У.', '', false],
            ['', 'Калініна А.В.', false],
            ['Беўза Л.В.', 'Беўза Л. Віктаравіч', true],
            ['Дорахава А. Аляксееўна', 'Дорахава А.П.', false],
            ['Сідарук', 'Сідарук Д.І.', false],
            ['Шук Н.', 'Шук', false],
            ['Варанько', 'Вишневская', false],
            ['Булацкая В.П.', 'Булацкая В.Ю.', false],
            ['Клівец Марыя Савельеўна', 'Клівец Марыя Савеліўна', true],
            ['Мацкевіч Аляксандра Васільеўна', 'Мацкевіч Алена Васільеўна', false],
            ['Няборская Алена Алегаўна', 'Няборская Алена Аляксееўна', false],
            ['Варанько', 'Варанько', false],
            ['Шуляк Лідзія Мікалаеўна', 'Лідзія Мікалаеўна Шуляк', true],
            ['Гетманчук В.Н.', 'Гетманчук Н.В.', false],
        ];
    }

    public function testIsSameManyNames(): void
    {
        $name1 = 'Мацкевіч Аляксандра Васільеўна';
        $name2 = 'Мацкевіч А. Васільеўна';
        $name3 = 'Мацкевіч Аляксандра В.';
        $name4 = 'Мацкевіч А.В.';
        $name5 = 'Мацкевіч А.';
        $name6 = 'Мацкевіч Аляксандра';

        $this->assertTrue($this->personService->isSameNames($name1, $name2));
        $this->assertTrue($this->personService->isSameNames($name1, $name3));
        $this->assertTrue($this->personService->isSameNames($name1, $name4));
        $this->assertTrue($this->personService->isSameNames($name1, $name5));
        $this->assertTrue($this->personService->isSameNames($name1, $name6));
        $this->assertTrue($this->personService->isSameNames($name2, $name3));
        $this->assertTrue($this->personService->isSameNames($name2, $name4));
        $this->assertTrue($this->personService->isSameNames($name2, $name5));
        $this->assertTrue($this->personService->isSameNames($name2, $name6));
        $this->assertTrue($this->personService->isSameNames($name3, $name4));
        $this->assertTrue($this->personService->isSameNames($name3, $name5));
        $this->assertTrue($this->personService->isSameNames($name3, $name6));
        $this->assertTrue($this->personService->isSameNames($name4, $name5));
        $this->assertTrue($this->personService->isSameNames($name4, $name6));
        $this->assertTrue($this->personService->isSameNames($name5, $name6));
    }

    /**
     * @dataProvider dataFullNameProvider
     */
    public function testFullName(string $nameA, string $nameB, string $expected): void
    {
        $result = $this->personService->getFullName($nameA, $nameB);

        $this->assertEquals($expected, $result, 'Error for names: ' . $nameA . ', ' . $nameB . ' => ' . $expected);
    }

    private function dataFullNameProvider(): array
    {
        return [
            ['Смольская А. А.', 'Смольская А.А.', 'Смольская А.А.'],
            ['Яскевіч Алена Аляксандраўна', 'Яскевіч А.А.', 'Яскевіч Алена Аляксандраўна'],
            ['Пашынская Г.', 'Пашынская Г.М.', 'Пашынская Г.М.'],
            ['Наважылава Н. Алегаўна', 'Наважылава Надзея', 'Наважылава Надзея Алегаўна'],
            ['Беўза Л.В.', 'Беўза Л. Віктаравіч', 'Беўза Л. Віктаравіч'],
            ['Шуляк Лідзія Мікалаеўна', 'Лідзія Мікалаеўна Шуляк', 'Лідзія Мікалаеўна Шуляк'],
        ];
    }

    public function testGetManyFullNames(): void
    {
        $name1 = 'Яскевіч А.';
        $name2 = 'Яскевіч А.А.';
        $name3 = 'Яскевіч Алена';
        $name4 = 'Яскевіч А. Аляксандрана';
        $name5 = 'Яскевіч Ален Аляксандраўна';

        $result = $this->personService->getFullName($name1, $name2);
        $result = $this->personService->getFullName($result, $name3);
        $result = $this->personService->getFullName($name4, $result);
        $result = $this->personService->getFullName($result, $name5);

        $this->assertEquals('Яскевіч Алена Аляксандраўна', $result);
        $this->assertTrue($this->personService->isSameNames($result, $name1));
        $this->assertTrue($this->personService->isSameNames($result, $name2));
        $this->assertTrue($this->personService->isSameNames($result, $name3));
        $this->assertTrue($this->personService->isSameNames($result, $name4));
        $this->assertTrue($this->personService->isSameNames($result, $name5));
    }
}
