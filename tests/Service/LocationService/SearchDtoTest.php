<?php

declare(strict_types=1);

namespace App\Tests\Service\LocationService;

use App\Entity\Type\GeoPointType;
use App\Helper\TextHelper;
use App\Repository\GeoPointRepository;
use App\Service\LocationService;
use PHPUnit\Framework\TestCase;

class SearchDtoTest extends TestCase
{
    private LocationService $locationService;

    public function setUp(): void
    {
        parent::setUp();

        $geoPointRepository = $this->createMock(GeoPointRepository::class);
        $textHelper = new TextHelper();
        $this->locationService = new LocationService($geoPointRepository, $textHelper);
    }

    public function testGetGeoPointSearchLetterO(): void
    {
        $dto = $this->locationService->getSearchDto('Млынарова', 'Бярэзінскі раён');

        $this->assertNull($dto->region);
        $this->assertEquals('Бярэзінскі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Млынарова', $dto->names[0]);
        $this->assertEquals('Млынарава', $dto->names[1]);
        $this->assertEquals('Мланарова', $dto->names[2]);
    }

    public function testGetGeoPointSearchVillage(): void
    {
        $dto = $this->locationService->getSearchDto('в. Альшанка', 'Бярэзінскі раён');

        $this->assertNull($dto->region);
        $this->assertEquals('Бярэзінскі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Альшанка', $dto->names[0]);
        $this->assertEquals('Альтанка', $dto->names[1]);
    }

    public function testGetGeoPointSearchCity(): void
    {
        $dto = $this->locationService->getSearchDto('г. Ліда');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertCount(1, $dto->prefixes);
        $this->assertEquals(GeoPointType::BE_TOWN, $dto->prefixes[0]);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Ліда', $dto->names[0]);
        $this->assertEquals('Ляда', $dto->names[1]);
    }

    public function testGetGeoPointSearchLetterE(): void
    {
        $dto = $this->locationService->getSearchDto('Новая Метча', 'Барысаўскі раён');

        $this->assertNull($dto->region);
        $this->assertEquals('Барысаўскі раён', $dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(6, $dto->names);
        $this->assertEquals('Новая Метча', $dto->names[0]);
        $this->assertEquals('Новая Мётча', $dto->names[1]);
        $this->assertEquals('Новая Метчча', $dto->names[2]);
        $this->assertEquals('Навая Метча', $dto->names[3]);
        $this->assertEquals('Новая Мятча', $dto->names[4]);
        $this->assertEquals('Новае Метча', $dto->names[5]);
    }

    public function testGetGeoPointSearchLetterJO(): void
    {
        $dto = $this->locationService->getSearchDto('Заёнкі');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Заёнкі', $dto->names[0]);
        $this->assertEquals('Заенкі', $dto->names[1]);
        $this->assertEquals('Заёнка', $dto->names[2]);
    }

    public function testGetGeoPointSearchHighLetters(): void
    {
        $dto = $this->locationService->getSearchDto('в. Старыя елкі');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Старыя Елкі', $dto->names[0]);
        $this->assertEquals('Старые Елкі', $dto->names[1]);
        $this->assertEquals('Старая Елкі', $dto->names[2]);
        $this->assertEquals('Старыя Елка', $dto->names[3]);
    }

    public function testGetGeoPointSearchLetterLastI(): void
    {
        $dto = $this->locationService->getSearchDto('Кайшоўкі');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Кайшоўкі', $dto->names[0]);
        $this->assertEquals('Кайшаўкі', $dto->names[1]);
        $this->assertEquals('Кайтоўкі', $dto->names[2]);
        $this->assertEquals('Кайшоўка', $dto->names[3]);
    }

    public function testGetGeoPointSearchLastLetterE(): void
    {
        $dto = $this->locationService->getSearchDto('в. Заброддзе');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Заброддзе', $dto->names[0]);
        $this->assertEquals('Забраддзе', $dto->names[1]);
        $this->assertEquals('Заброддзя', $dto->names[2]);
    }

    public function testGetGeoPointSearchLetterELIE(): void
    {
        $dto = $this->locationService->getSearchDto('Андрэлевічы');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(6, $dto->names);
        $this->assertEquals('Андрэлевічы', $dto->names[0]);
        $this->assertEquals('Андрэлёвічы', $dto->names[1]);
        $this->assertEquals('Андрэлевіча', $dto->names[2]);
        $this->assertEquals('Андрэлявічы', $dto->names[3]);
        $this->assertEquals('Андрэевічы', $dto->names[4]);
        $this->assertEquals('Андрэлевячы', $dto->names[5]);
    }

    public function testGetGeoPointSearchLetterI(): void
    {
        $dto = $this->locationService->getSearchDto('Любичыцы');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Любічыцы', $dto->names[0]);
        $this->assertEquals('Любічыца', $dto->names[1]);
        $this->assertEquals('Любічацы', $dto->names[2]);
        $this->assertEquals('Любячыцы', $dto->names[3]);
    }

    public function testGetGeoPointSearchGPspace(): void
    {
        $dto = $this->locationService->getSearchDto('г. п. Парычы');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertCount(1, $dto->prefixes);
        $this->assertEquals(GeoPointType::BE_URBAN_SETTLEMENT, $dto->prefixes[0]);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Парычы', $dto->names[0]);
        $this->assertEquals('Парыча', $dto->names[1]);
        $this->assertEquals('Парачы', $dto->names[2]);
    }

    public function testGetGeoPointSearchSettlement(): void
    {
        $dto = $this->locationService->getSearchDto('п. Ушачы');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertCount(2, $dto->prefixes);
        $this->assertEquals(GeoPointType::BE_SETTLEMENT, $dto->prefixes[0]);
        $this->assertEquals(GeoPointType::BE_URBAN_SETTLEMENT, $dto->prefixes[1]);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Ушачы', $dto->names[0]);
        $this->assertEquals('Ушача', $dto->names[1]);
        $this->assertEquals('Утачы', $dto->names[2]);
    }

    public function testGetGeoPointSearchGlusk(): void
    {
        $dto = $this->locationService->getSearchDto('г. п. Глуск', 'Асіповіцкі раён');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district); // For URBAN_SETTLEMENT to NULL
        $this->assertCount(1, $dto->prefixes);
        $this->assertEquals(GeoPointType::BE_URBAN_SETTLEMENT, $dto->prefixes[0]);
        $this->assertCount(1, $dto->names);
        $this->assertEquals('Глуск', $dto->names[0]);
    }

    public function testGetGeoPointSearchLastLetterEpp(): void
    {
        $dto = $this->locationService->getSearchDto('в. Навасёлкі');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Навасёлкі', $dto->names[0]);
        $this->assertEquals('Наваселкі', $dto->names[1]);
        $this->assertEquals('Навасёлка', $dto->names[2]);
    }

    public function testGetGeoPointSearchLastLetterEE(): void
    {
        $dto = $this->locationService->getSearchDto('Мядзведзеўка');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(5, $dto->names);
        $this->assertEquals('Мядзведзеўка', $dto->names[0]);
        $this->assertEquals('Мядзвёдзеўка', $dto->names[1]);
        $this->assertEquals('Мядзведзёўка', $dto->names[2]);
        $this->assertEquals('Мядзвядзеўка', $dto->names[3]);
        $this->assertEquals('Медзведзеўка', $dto->names[4]);
    }

    public function testGetGeoPointSearchLastLetterAE(): void
    {
        $dto = $this->locationService->getSearchDto('в.Багатырскае');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Багатырскае', $dto->names[0]);
        $this->assertEquals('Багатырская', $dto->names[1]);
        $this->assertEquals('Багатарскае', $dto->names[2]);
    }

    public function testGetGeoPointSearchLastLetterSH(): void
    {
        $dto = $this->locationService->getSearchDto('Палаша');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Палаша', $dto->names[0]);
        $this->assertEquals('Палата', $dto->names[1]);
    }

    public function testGetGeoPointSearchLetterEJ(): void
    {
        $dto = $this->locationService->getSearchDto('Сеймурадцы');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(5, $dto->names);
        $this->assertEquals('Сеймурадцы', $dto->names[0]);
        $this->assertEquals('Сёймурадцы', $dto->names[1]);
        $this->assertEquals('Сямурадцы', $dto->names[2]);
        $this->assertEquals('Сеймурадца', $dto->names[3]);
        $this->assertEquals('Сяймурадцы', $dto->names[4]);
    }

    public function testGetGeoPointSearchGPslash(): void
    {
        $dto = $this->locationService->getSearchDto('г/п Адрыжын');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertCount(1, $dto->prefixes);
        $this->assertEquals(GeoPointType::BE_URBAN_SETTLEMENT, $dto->prefixes[0]);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Адрыжын', $dto->names[0]);
        $this->assertEquals('Адражын', $dto->names[1]);
    }

    public function testGetGeoPointSearchGP(): void
    {
        $dto = $this->locationService->getSearchDto('г.п. Бешанковічы');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertCount(1, $dto->prefixes);
        $this->assertEquals(GeoPointType::BE_URBAN_SETTLEMENT, $dto->prefixes[0]);
        $this->assertCount(7, $dto->names);
        $this->assertEquals('Бешанковічы', $dto->names[0]);
        $this->assertEquals('Бёшанковічы', $dto->names[1]);
        $this->assertEquals('Бешанковіча', $dto->names[2]);
        $this->assertEquals('Бешанкавічы', $dto->names[3]);
        $this->assertEquals('Бяшанковічы', $dto->names[4]);
        $this->assertEquals('Бетанковічы', $dto->names[5]);
        $this->assertEquals('Бешанковячы', $dto->names[6]);
    }

    public function testGetGeoPointLetterIa(): void
    {
        $dto = $this->locationService->getSearchDto('г/п Падсвілля');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertCount(1, $dto->prefixes);
        $this->assertEquals(GeoPointType::BE_URBAN_SETTLEMENT, $dto->prefixes[0]);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Падсвілля', $dto->names[0]);
        $this->assertEquals('Падсвілле', $dto->names[1]);
        $this->assertEquals('Падсвялля', $dto->names[2]);
    }

    public function testGetGeoPointSearchMinsk(): void
    {
        $dto = $this->locationService->getSearchDto('г. Мінск', 'Мінск');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertCount(1, $dto->prefixes);
        $this->assertEquals(GeoPointType::BE_TOWN, $dto->prefixes[0]);
        $this->assertCount(2, $dto->names);
        $this->assertEquals('Мінск', $dto->names[0]);
        $this->assertEquals('Мянск', $dto->names[1]);
    }

    public function testGetGeoPointSearchLetterII(): void
    {
        $dto = $this->locationService->getSearchDto('Радутичи');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Радуцічы', $dto->names[0]);
        $this->assertEquals('Радуціча', $dto->names[1]);
        $this->assertEquals('Радуцячы', $dto->names[2]);
    }

    public function testGetGeoPointSearchBabrujsk(): void
    {
        $dto = $this->locationService->getSearchDto('г. Бабруйск', 'Бабруйскі раён');

        $this->assertNull($dto->region);
        $this->assertEquals('Бабруйскі раён', $dto->district);
        $this->assertCount(1, $dto->prefixes);
        $this->assertEquals(GeoPointType::BE_TOWN, $dto->prefixes[0]);
        $this->assertCount(1, $dto->names);
        $this->assertEquals('Бабруйск', $dto->names[0]);
    }

    public function testGetGeoPointSearchLetterN(): void
    {
        $dto = $this->locationService->getSearchDto('Вязычынь');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(4, $dto->names);
        $this->assertEquals('Вязычынь', $dto->names[0]);
        $this->assertEquals('Вязычын', $dto->names[1]);
        $this->assertEquals('Везычынь', $dto->names[2]);
        $this->assertEquals('Вязачынь', $dto->names[3]);
    }

    public function testGetGeoPointSearchLetterEIA(): void
    {
        $dto = $this->locationService->getSearchDto('в. Песчанка');

        $this->assertNull($dto->region);
        $this->assertNull($dto->district);
        $this->assertEquals(GeoPointType::BE_VILLAGE_LONGS, $dto->prefixes);
        $this->assertCount(3, $dto->names);
        $this->assertEquals('Песчанка', $dto->names[0]);
        $this->assertEquals('Пёсчанка', $dto->names[1]);
        $this->assertEquals('Пясчанка', $dto->names[2]);
    }
}
