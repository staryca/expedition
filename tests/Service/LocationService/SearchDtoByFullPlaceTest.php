<?php

declare(strict_types=1);

namespace App\Tests\Service\LocationService;

use App\Entity\Type\GeoPointType;
use App\Repository\GeoPointRepository;
use App\Service\LocationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchDtoByFullPlaceTest extends TestCase
{
    private LocationService $locationService;

    public function setUp(): void
    {
        parent::setUp();

        $geoPointRepository = $this->createMock(GeoPointRepository::class);
        $this->locationService = new LocationService($geoPointRepository);
    }

    public function testBrackets(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Забор’е (Расонскі р-н, Краснапольскі с/с)');

        $this->assertNull($dto->region);
        $this->assertEquals('Расонскі раён', $dto->district);
        $this->assertEquals('Краснапольскі сельскі Савет', $dto->subDistrict);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Забор’е', $dto->names[0]);
        $this->assertEquals('Забар’е', $dto->names[1]);
        $this->assertEquals('Забор’я', $dto->names[2]);
    }

    public function testSimple(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Клясьціцы, Расонскі р-н');

        $this->assertNull($dto->region);
        $this->assertEquals('Расонскі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(5, $dto->names);
        $this->assertEquals('Клясьціцы', $dto->names[0]);
        $this->assertEquals('Клясьціца', $dto->names[1]);
        $this->assertEquals('Клесьціцы', $dto->names[2]);
        $this->assertEquals('Клясьцяцы', $dto->names[3]);
        $this->assertEquals('Клясціцы', $dto->names[4]);
    }

    public function testOtherWords(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Казлы, Расонскі р-н падарожнае апытанне');

        $this->assertNull($dto->region);
        $this->assertEquals('Расонскі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Казлы', $dto->names[0]);
        $this->assertEquals('Казла', $dto->names[1]);
    }

    public function testWithCommaAsSymbal(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Ю`ркава, Глыбоцкі раён');

        $this->assertNull($dto->region);
        $this->assertEquals('Глыбоцкі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(1, $dto->names);
        $this->assertEquals('Юркава', $dto->names[0]);
    }
    public function testWithComma(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Параф\'янава, Сітцаўскі сельсавет, Докшыцкі раён
');

        $this->assertNull($dto->region);
        $this->assertEquals('Докшыцкі раён', $dto->district);
        $this->assertEquals('Сітцаўскі сельскі Савет', $dto->subDistrict);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Параф’янава', $dto->names[0]);
        $this->assertEquals('Параф’енава', $dto->names[1]);
    }

    public function testWithoutComma(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Амосенкі Расонскі р-н');

        $this->assertNull($dto->region);
        $this->assertEquals('Расонскі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(5, $dto->names);
        $this->assertEquals('Амосенкі', $dto->names[0]);
        $this->assertEquals('Амосёнкі', $dto->names[1]);
        $this->assertEquals('Амасенкі', $dto->names[2]);
        $this->assertEquals('Амосянкі', $dto->names[3]);
        $this->assertEquals('Амосенка', $dto->names[4]);
    }

    public function testTwoNames(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('аг. Смальяны [Смаляны], Аршанскі раён (Смольянский c/c)');

        $this->assertNull($dto->region);
        $this->assertEquals('Аршанскі раён', $dto->district);
        $this->assertEquals([GeoPointType::BE_AGRO_CITY], $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Смальяны', $dto->names[0]);
        $this->assertEquals('Смальены', $dto->names[1]);
        $this->assertEquals('Смальяна', $dto->names[2]);
        $this->assertEquals('Смаляны', $dto->names[3]);
    }

    public function testRevertBlocks(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('Крупскі раён, Самасадаўка');

        $this->assertNull($dto->region);
        $this->assertEquals('Крупскі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Самаседаўка', $dto->names[0]);
        $this->assertEquals('Самасёдаўка', $dto->names[1]);
        $this->assertEquals('Самасядаўка', $dto->names[2]);
    }

    public function testMiesto(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('Сенненскі раён, мяс. Багушэўск');

        $this->assertNull($dto->region);
        $this->assertEquals('Сенненскі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Багушэўск', $dto->names[0]);
        $this->assertEquals('Багутэўск', $dto->names[1]);
    }

    public function testWithRegion(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Каржавы (Высокаўскі с/с), Аршанскі раён, Віцебская вобласць, Беларусь');

        $this->assertEquals('Віцебская вобласць', $dto->region);
        $this->assertEquals('Аршанскі раён', $dto->district);
        $this->assertEquals('Высокаўскі сельскі Савет', $dto->subDistrict);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Каржавы', $dto->names[0]);
        $this->assertEquals('Каржава', $dto->names[1]);
    }

    public function testHutar(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('хутар Калодавічы (Дабрамысленскі с/с, Лёзненскі раён), цяпер не існуе');

        $this->assertNull($dto->region);
        $this->assertEquals('Лёзненскі раён', $dto->district);
        $this->assertEquals('Дабрамысленскі сельскі Савет', $dto->subDistrict);
        $this->assertEquals([GeoPointType::BE_KHUTOR], $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Калодавічы', $dto->names[0]);
        $this->assertEquals('Калодавіча', $dto->names[1]);
        $this->assertEquals('Каладавічы', $dto->names[2]);
        $this->assertEquals('Калодавячы', $dto->names[3]);
    }

    public function testReversOrder(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('Магілёўская вобласць, Дрыбінскі р-н, в.Сялец');

        $this->assertEquals('Магілёўская вобласць', $dto->region);
        $this->assertEquals('Дрыбінскі раён', $dto->district);
        $this->assertNull($dto->subDistrict);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Сялец', $dto->names[0]);
        $this->assertEquals('Сялёц', $dto->names[1]);
        $this->assertEquals('Сяляц', $dto->names[2]);
        $this->assertEquals('Селец', $dto->names[3]);
    }

    public function testLower(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('Капаткевіцкі раён, кашэвічы');

        $this->assertNull($dto->region);
        $this->assertEquals('Капаткевіцкі раён', $dto->district);
        $this->assertNull($dto->subDistrict);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Кашэвічы', $dto->names[0]);
        $this->assertEquals('Кашэвіча', $dto->names[1]);
        $this->assertEquals('Катэвічы', $dto->names[2]);
        $this->assertEquals('Кашэвячы', $dto->names[3]);
    }

    public function testNameWithPoint(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('Глыбоцкі раён, в. Забор.е');

        $this->assertNull($dto->region);
        $this->assertEquals('Глыбоцкі раён', $dto->district);
        $this->assertNull($dto->subDistrict);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Забор.е', $dto->names[0]);
        $this->assertEquals('Забар.е', $dto->names[1]);
        $this->assertEquals('Забор.я', $dto->names[2]);
        $this->assertEquals('Забор’е', $dto->names[3]);
    }

    public function testNameWithLongText(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Кашэвічы, Петрыкаўскага раёна Гомельскай вобласці');

        $this->assertEquals('Гомельская вобласць', $dto->region);
        $this->assertEquals('Петрыкаўскі раён', $dto->district);
        $this->assertNull($dto->subDistrict);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Кашэвічы', $dto->names[0]);
        $this->assertEquals('Кашэвіча', $dto->names[1]);
        $this->assertEquals('Катэвічы', $dto->names[2]);
        $this->assertEquals('Кашэвячы', $dto->names[3]);
    }

    public function testUrbanSettlement(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('гп Лёзна, Лёзненскі раён, Віцебская вобласць, Беларусь');

        $this->assertEquals('Віцебская вобласць', $dto->region);
        $this->assertNull($dto->district); // For 'гарадскі пасёлак' district can be wrong
        $this->assertEquals([GeoPointType::BE_URBAN_SETTLEMENT], $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Лёзна', $dto->names[0]);
        $this->assertEquals('Лёзн', $dto->names[1]);
        $this->assertEquals('Лезна', $dto->names[2]);
    }

    public function testWithSovet(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Высачаны, Лёзненскі раён (Крынковский с/с)');

        $this->assertNull($dto->region);
        $this->assertEquals('Лёзненскі раён', $dto->district);
        $this->assertEquals('Крынкаўскі сельскі Савет', $dto->subDistrict);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Высачаны', $dto->names[0]);
        $this->assertEquals('Васачаны', $dto->names[1]);
    }

    public function testNameSecond(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Рублева - 2 Бабінавіцкі с/с, Лёзненскі раён, Віцебская вобласць, Беларусь');

        $this->assertEquals('Віцебская вобласць', $dto->region);
        $this->assertEquals('Лёзненскі раён', $dto->district);
        $this->assertEquals('Бабінавіцкі сельскі Савет', $dto->subDistrict);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Рублева', $dto->names[0]); // without '-2' only for this village
        $this->assertEquals('Рублёва', $dto->names[1]);
        $this->assertEquals('Рублява', $dto->names[2]);
    }

    public function testDistrictCase(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Вічын Лунінецкага раёна Брэсцкай вобл.');

        $this->assertEquals('Брэсцкая вобласць', $dto->region);
        $this->assertEquals('Лунінецкі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Вічын', $dto->names[0]);
        $this->assertEquals('Вічан', $dto->names[1]);
        $this->assertEquals('Вячын', $dto->names[2]);
    }

    public function testNavalukomal(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Новалукомль Віцебскай вобласці');

        $this->assertEquals('Віцебская вобласць', $dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals([GeoPointType::BE_TOWN], $dto->prefixes);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Новалукомль', $dto->names[0]);
        $this->assertEquals('Навалукомль', $dto->names[1]);
    }

    public function testGreski(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('Т(Г)рэскага раён, Такавішча');

        $this->assertNull($dto->region);
        $this->assertEquals('Слуцкі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Такавішча', $dto->names[0]);
        $this->assertEquals('Такавішчча', $dto->names[1]);
        $this->assertEquals('Такавітча', $dto->names[2]);
        $this->assertEquals('Такавяшча', $dto->names[3]);
    }

    public function testShortPart(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('Бялыніцкі раён, Ст.Сяло');

        $this->assertNull($dto->region);
        $this->assertEquals('Бялыніцкі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(6, $dto->names);
        $this->assertEquals('Сяло', $dto->names[0]);
        $this->assertEquals('Сяла', $dto->names[1]);
        $this->assertEquals('Село', $dto->names[2]);
        $this->assertEquals('Старое Сяло', $dto->names[3]);
        $this->assertEquals('Стары Сяло', $dto->names[4]);
        $this->assertEquals('Старая Сяло', $dto->names[5]);
    }

    public function testTwoParts(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('Мсціслаўскі раён, Вялікая Багацькаўка');

        $this->assertNull($dto->region);
        $this->assertEquals('Мсціслаўскі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Вялікая Багацькаўка', $dto->names[0]);
        $this->assertEquals('Велікая Багацькаўка', $dto->names[1]);
        $this->assertEquals('Вялякая Багацькаўка', $dto->names[2]);
        $this->assertEquals('Багацькаўка', $dto->names[3]);
    }

    public function testMaybeSubdistrict(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('Слуцкі раён, Белькавічы, Рачкавіцкі	');

        $this->assertNull($dto->region);
        $this->assertEquals('Слуцкі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(5, $dto->names);
        $this->assertEquals('Белькавічы', $dto->names[0]);
        $this->assertEquals('Бёлькавічы', $dto->names[1]);
        $this->assertEquals('Белькавіча', $dto->names[2]);
        $this->assertEquals('Бялькавічы', $dto->names[3]);
        $this->assertEquals('Белькавячы', $dto->names[4]);
    }

    public function testWithoutDistrict(): void
    {
        $dto = $this->locationService->getSearchDtoByFullPlace('в. Рублева -2, Бабінавіцкі с/с');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals('Бабінавіцкі сельскі Савет', $dto->subDistrict);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Рублева', $dto->names[0]); // without '-2' only for this village
        $this->assertEquals('Рублёва', $dto->names[1]);
        $this->assertEquals('Рублява', $dto->names[2]);
    }
}
