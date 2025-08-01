<?php

declare(strict_types=1);

namespace App\Tests\Service\PersonService;

use App\Dto\OrganizationDto;
use App\Entity\Type\GenderType;
use App\Service\PersonService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class OrganizationTest extends TestCase
{
    private readonly PersonService $personService;

    public function setUp(): void
    {
        parent::setUp();

        $this->personService = new PersonService();
    }

    public function testParseOrganizationSimple(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'сямейны ансамбль Гусковы.';

        $this->personService->parseOrganization($organization);
        $this->assertCount(0, $organization->informants);

        $this->assertEquals('сямейны ансамбль Гусковы', $organization->name);
        $this->assertNull($organization->informantText);
    }

    public function testParseOrganizationLeader(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'ансамбль "Медуніца": Замастоцкая В.В.';

        $this->personService->parseOrganization($organization);

        $this->assertEquals('ансамбль "Медуніца"', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(1, $organization->informants);
        $this->assertEquals('Замастоцкая В.В.', $organization->informants[0]->name);
    }

    public function testParseOrganizationOnly1Name(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Наташа Башкірава (13 гадоў)';
        $organization->dateAdded = Carbon::create(2000); // Year of report

        $this->personService->parseOrganization($organization);

        $this->assertEquals('', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(1, $organization->informants);
        $this->assertEquals('Башкірава Наташа', $organization->informants[0]->name);
        $this->assertEquals('', $organization->informants[0]->notes);
        $this->assertEquals(GenderType::FEMALE, $organization->informants[0]->gender);
        $this->assertEquals(1987, $organization->informants[0]->birth);
    }

    public function testParseOrganizationAsPerson(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Голік Васіль Пятровіч, 1942 г.н. ';

        $this->personService->parseOrganization($organization);
        $this->assertEquals('', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(1, $organization->informants);
        $this->assertEquals('Голік Васіль Пятровіч', $organization->informants[0]->name);
        $this->assertEquals('', $organization->informants[0]->notes);
        $this->assertEquals(1942, $organization->informants[0]->birth);
        $this->assertCount(0, $organization->informants[0]->locations);
    }

    public function testParseOrganizationOnlyName(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Група дзяўчат - удзельнікі мастацкай самадзейнасці';

        $this->personService->parseOrganization($organization);
        $this->assertEquals('Група дзяўчат - удзельнікі мастацкай самадзейнасці', $organization->name);
        $this->assertEquals('', $organization->informantText);
        $this->assertCount(0, $organization->informants);
    }

    public function testParseOrganizationOnlyOnePerson(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'загадчыца Плянтаўскага сельскага клуба Валянціна Кураша Мікалаеўна';

        $this->personService->parseOrganization($organization);
        $this->assertEquals('', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(1, $organization->informants);
        $this->assertEquals('Кураша Валянціна Мікалаеўна', $organization->informants[0]->name);
        $this->assertEquals('загадчыца Плянтаўскага сельскага клуба', $organization->informants[0]->notes);
        $this->assertNull($organization->informants[0]->birth);
        $this->assertCount(0, $organization->informants[0]->locations);
    }

    public function testParseOrganizationWithEnter(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Народны фальклорна-этнаграфічны калектыў "Журавушка": Яхнавец Ганна (1942)
Касцюк Ніна (1938)
Вабішчэвіч Надзея (1940)  ';

        $this->personService->parseOrganization($organization);

        $this->assertEquals('Народны фальклорна-этнаграфічны калектыў "Журавушка"', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(3, $organization->informants);

        $informant = $organization->informants[0];
        $this->assertEquals('Яхнавец Ганна', $informant->name);
        $this->assertEquals(1942, $informant->birth);

        $informant = $organization->informants[1];
        $this->assertEquals('Касцюк Ніна', $informant->name);
        $this->assertEquals(1938, $informant->birth);

        $informant = $organization->informants[2];
        $this->assertEquals('Вабішчэвіч Надзея', $informant->name);
        $this->assertEquals(1940, $informant->birth);
    }

    public function testParseOrganizationLeaderStr(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'фальклорны калектыў: кiр. Лобач З. I.';

        $this->personService->parseOrganization($organization);
        $this->assertCount(1, $organization->informants);
        $this->assertEquals('Лобач З. І.', $organization->informants[0]->name);
        $this->assertEquals('кір.', $organization->informants[0]->notes);

        $this->assertEquals('фальклорны калектыў', $organization->name);
        $this->assertNull($organization->informantText);
    }

    public function testParseOrganizationManyPeoplePointComma(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Параска Хведараўна Казак;Яўгіння Радзівонаўна Фясько;М.П.Дрэнь';

        $this->personService->parseOrganization($organization);
        $this->assertCount(3, $organization->informants);
        $this->assertEquals('Казак Параска Хведараўна', $organization->informants[0]->name);
        $this->assertEquals('Фясько Яўгіння Радзівонаўна', $organization->informants[1]->name);
        $this->assertEquals('Дрэнь М.П.', $organization->informants[2]->name);

        $this->assertEquals('', $organization->name);
        $this->assertNull($organization->informantText);
    }

    public function testParseOrganizationManyPeopleComma(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Ярашэнка Я.А., Максіменка А.А., Бугач Г.А., Жалязко Т.І., Ярашэнка Я.А., Ярашэнка Е.С., Крыжалінская Н.М., Атаманская Я.А.';

        $this->personService->parseOrganization($organization);
        $this->assertCount(7, $organization->informants);
        $this->assertEquals('Ярашэнка Я.А.', $organization->informants[0]->name);
        $this->assertEquals('Максіменка А.А.', $organization->informants[1]->name);
        $this->assertEquals('Бугач Г.А.', $organization->informants[2]->name);
        $this->assertEquals('Жалязко Т.І.', $organization->informants[3]->name);
        $this->assertEquals('Ярашэнка Е.С.', $organization->informants[4]->name);
        $this->assertEquals('Крыжалінская Н.М.', $organization->informants[5]->name);
        $this->assertEquals('Атаманская Я.А.', $organization->informants[6]->name);

        $this->assertEquals('', $organization->name);
        $this->assertNull($organization->informantText);
    }

    public function testParseOrganizationOneName(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Клімовіч Г.О';

        $this->personService->parseOrganization($organization);
        $this->assertCount(1, $organization->informants);
        $this->assertEquals('Клімовіч Г.О.', $organization->informants[0]->name);

        $this->assertEquals('', $organization->name);
        $this->assertNull($organization->informantText);
    }

    public function testParseOrganizationOneFullName(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Мурач Надзея Аляксандраўна';

        $this->personService->parseOrganization($organization);
        $this->assertCount(1, $organization->informants);
        $this->assertEquals('Мурач Надзея Аляксандраўна', $organization->informants[0]->name);
        $this->assertEquals(GenderType::FEMALE, $organization->informants[0]->gender);

        $this->assertEquals('', $organization->name);
        $this->assertNull($organization->informantText);
    }

    public function testParseOrganizationInBrackets(): void
    {
        $organization = new OrganizationDto();
        $organization->name = '[працяг на 20-82-12] [Е. Ясько, М. Гунько,  К. Пачуйка] ';

        $this->personService->parseOrganization($organization);
        $this->assertCount(3, $organization->informants);
        $this->assertEquals('Ясько Е.', $organization->informants[0]->name);
        $this->assertEquals('Гунько М.', $organization->informants[1]->name);
        $this->assertEquals('Пачуйка К.', $organization->informants[2]->name);

        $this->assertEquals('', $organization->name);
        $this->assertNull($organization->informantText);
    }

    public function testParseOrganizationOnlyPerson(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Селіванава Наталля Юр\'еўна';

        $this->personService->parseOrganization($organization);

        $this->assertEquals('', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(1, $organization->informants);
        $this->assertEquals('Селіванава Наталля Юр\'еўна', $organization->informants[0]->name);
    }

    public function testParseOrganizationKozManyInformants(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Загадчыца Беразлянскага клубу Шуляк Лідзія Мікалаеўна, жанчыны мясцовага калектыву:
            Алена Сцяпанаўна Гетманчук, 1937 г.н., Алена Данілаўна Гетманчук, 1946 г.н., Вера Гетманчук, 1936 г.н.,
            Любоў Луцэвіч, 1937 г.н., Ганна Шуляк, 1931 г.н., Лідзія Мікалаеўна Шуляк, 1958 г.н. ';

        $this->personService->parseOrganization($organization);

        $this->assertEquals('жанчыны мясцовага калектыву', $organization->name);
        $this->assertCount(6, $organization->informants);

        $informant = $organization->informants[0];
        $this->assertEquals('Гетманчук Алена Сцяпанаўна', $informant->name);
        $this->assertEquals(1937, $informant->birth);

        $informant = $organization->informants[1];
        $this->assertEquals('Гетманчук Алена Данілаўна', $informant->name);
        $this->assertEquals(1946, $informant->birth);

        $informant = $organization->informants[2];
        $this->assertEquals('Гетманчук Вера', $informant->name);
        $this->assertEquals(1936, $informant->birth);

        $informant = $organization->informants[3];
        $this->assertEquals('Луцэвіч Любоў', $informant->name);
        $this->assertEquals(1937, $informant->birth);

        $informant = $organization->informants[4];
        $this->assertEquals('Шуляк Ганна', $informant->name);
        $this->assertEquals(1931, $informant->birth);

        $informant = $organization->informants[5];
        $this->assertEquals('Шуляк Лідзія Мікалаеўна', $informant->name);
        $this->assertEquals(1958, $informant->birth);
        $this->assertEquals('Загадчыца Беразлянскага клубу', $informant->notes);
    }

    public function testParseOrganizationNameOnly2(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Жаночы фальклорны калектыў вёскі Крамно:
            Вольга Шум, 1930 г.н., Анастасія Мартыновіч, 1930 г.н.,
            Вольга Крэйдзіч, 1932 г.н., Вольга Каласей, 1932 г.н., Дар\'я Шум, 1935 г.н., Ганна Міснік, 1933 г.н. ';

        $this->personService->parseOrganization($organization);

        $this->assertEquals('Жаночы фальклорны калектыў вёскі Крамно', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(6, $organization->informants);

        $informant = $organization->informants[0];
        $this->assertEquals('Шум Вольга', $informant->name);
        $this->assertEquals(1930, $informant->birth);

        $informant = $organization->informants[1];
        $this->assertEquals('Мартыновіч Анастасія', $informant->name);
        $this->assertEquals(1930, $informant->birth);

        $informant = $organization->informants[2];
        $this->assertEquals('Крэйдзіч Вольга', $informant->name);
        $this->assertEquals(1932, $informant->birth);

        $informant = $organization->informants[3];
        $this->assertEquals('Каласей Вольга', $informant->name);
        $this->assertEquals(1932, $informant->birth);

        $informant = $organization->informants[4];
        $this->assertEquals('Шум Дар\'я', $informant->name);
        $this->assertEquals(1935, $informant->birth);

        $informant = $organization->informants[5];
        $this->assertEquals('Міснік Ганна', $informant->name);
        $this->assertEquals(1933, $informant->birth);
    }

    public function testParseOrganizationWithoutYears(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Народны фальклорны ансамбль "Жураўка":
            Кацярына Мікалаеўна Мазько, Лідзія Мікалаеўна Балюк,
            Вольга Дзмітрыеўна Балюк, Кацярына Зіноўеўна Балюк, Таццяна Васільеўна Балюк, Марыя Мікалаеўна Малашчук. ';

        $this->personService->parseOrganization($organization);

        $this->assertEquals('Народны фальклорны ансамбль "Жураўка"', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(6, $organization->informants);

        $informant = $organization->informants[0];
        $this->assertEquals('Мазько Кацярына Мікалаеўна', $informant->name);
        $this->assertNull($informant->birth);

        $informant = $organization->informants[1];
        $this->assertEquals('Балюк Лідзія Мікалаеўна', $informant->name);
        $this->assertNull($informant->birth);

        $informant = $organization->informants[2];
        $this->assertEquals('Балюк Вольга Дзмітрыеўна', $informant->name);
        $this->assertNull($informant->birth);

        $informant = $organization->informants[3];
        $this->assertEquals('Балюк Кацярына Зіноўеўна', $informant->name);
        $this->assertNull($informant->birth);

        $informant = $organization->informants[4];
        $this->assertEquals('Балюк Таццяна Васільеўна', $informant->name);
        $this->assertNull($informant->birth);

        $informant = $organization->informants[5];
        $this->assertEquals('Малашчук Марыя Мікалаеўна', $informant->name);
        $this->assertNull($informant->birth);
    }

    public function testParseOrganizationOnlyNames(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Аляксандр Рыгоравіч Дублянін, Сцяпан Дзмітрыевіч Царык, Васіль Цімафеевіч Васечка';

        $this->personService->parseOrganization($organization);

        $this->assertEquals('', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(3, $organization->informants);

        $informant = $organization->informants[0];
        $this->assertEquals('Дублянін Аляксандр Рыгоравіч', $informant->name);
        $this->assertNull($informant->birth);

        $informant = $organization->informants[1];
        $this->assertEquals('Царык Сцяпан Дзмітрыевіч', $informant->name);
        $this->assertNull($informant->birth);

        $informant = $organization->informants[2];
        $this->assertEquals('Васечка Васіль Цімафеевіч', $informant->name);
        $this->assertNull($informant->birth);
    }

    public function testParseOrganizationOnlyNamesWithNotes(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Уладзімір Ануфрыевіч Крывенька (баян), Станіслаў Пятровіч Мялец [Мелец] (скрыпка), Віктар Антонавіч Буйко (лыжкі)';

        $this->personService->parseOrganization($organization);

        $this->assertEquals('', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(3, $organization->informants);

        $informant = $organization->informants[0];
        $this->assertEquals('Крывенька Уладзімір Ануфрыевіч', $informant->name);
        $this->assertEquals('баян', $informant->notes);
        $this->assertNull($informant->birth);
        $this->assertTrue($informant->isMusician);

        $informant = $organization->informants[1];
        $this->assertEquals('Мялец [Мелец] Станіслаў Пятровіч', $informant->name);
        $this->assertEquals('скрыпка', $informant->notes);
        $this->assertNull($informant->birth);
        $this->assertTrue($informant->isMusician);

        $informant = $organization->informants[2];
        $this->assertEquals('Буйко Віктар Антонавіч', $informant->name);
        $this->assertEquals('лыжкі', $informant->notes);
        $this->assertNull($informant->birth);
        $this->assertTrue($informant->isMusician);
    }

    public function testParseOrganizationKozManyInformantsSimple(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Фальклорны калектыў Беразлянскага СДК: Алена Сцяпанаўна Гетманчук, 1937 г.н.,
            Алена Данілаўна Гетманчук, 1946 г.н., Вера Дзмітрыеўна Гетманчук, 1936 г.н.,
            Любоў Андрэеўна Луцэвіч, 1937 г.н., Ганна Аляксееўна Шуляк, 1931 г.н., Лідзія Мікалаеўна Шуляк, 1958 г.н.,
            музыкант Якушка Аляксандр Васільевіч, 1956 г.н. (баян)';

        $this->personService->parseOrganization($organization);
        $this->assertEquals('Фальклорны калектыў Беразлянскага СДК', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(7, $organization->informants);

        $informant = $organization->informants[0];
        $this->assertEquals('Гетманчук Алена Сцяпанаўна', $informant->name);
        $this->assertEquals(1937, $informant->birth);

        $informant = $organization->informants[1];
        $this->assertEquals('Гетманчук Алена Данілаўна', $informant->name);
        $this->assertEquals(1946, $informant->birth);

        $informant = $organization->informants[2];
        $this->assertEquals('Гетманчук Вера Дзмітрыеўна', $informant->name);
        $this->assertEquals(1936, $informant->birth);

        $informant = $organization->informants[3];
        $this->assertEquals('Луцэвіч Любоў Андрэеўна', $informant->name);
        $this->assertEquals(1937, $informant->birth);

        $informant = $organization->informants[4];
        $this->assertEquals('Шуляк Ганна Аляксееўна', $informant->name);
        $this->assertEquals(1931, $informant->birth);

        $informant = $organization->informants[5];
        $this->assertEquals('Шуляк Лідзія Мікалаеўна', $informant->name);
        $this->assertEquals(1958, $informant->birth);

        $informant = $organization->informants[6];
        $this->assertEquals('Якушка Аляксандр Васільевіч', $informant->name);
        $this->assertEquals(1956, $informant->birth);
        $this->assertEquals('музыкант, баян', $informant->notes);
        $this->assertTrue($informant->isMusician);
    }

    public function testParseOrganizationKozOne(): void
    {
        $organization = new OrganizationDto();
        $organization->name = 'Васіль Кавалевіч, 1920 г.н. і Вольга Карагода, 1927 г.н. Музыкі:
            Дзмітрый Астапчук, 1920 г.н. (гармонік),
            Іван Краўчук, 1922 г.н. (скрыпка),
            Мікалай Кавалевіч, 1947 г.н. (бубен, мастацкі свіст). ';

        $this->personService->parseOrganization($organization);
        $this->assertEquals('', $organization->name);
        $this->assertEquals('', $organization->informantText);

        $this->assertCount(5, $organization->informants);

        $informant = $organization->informants[0];
        $this->assertEquals('Кавалевіч Васіль', $informant->name);
        $this->assertEquals(1920, $informant->birth);

        $informant = $organization->informants[1];
        $this->assertEquals('Карагода Вольга', $informant->name);
        $this->assertEquals(1927, $informant->birth);

        $informant = $organization->informants[2];
        $this->assertEquals('Астапчук Дзмітрый', $informant->name);
        $this->assertEquals(1920, $informant->birth);
        $this->assertEquals('музыкі, гармонік', $informant->notes);
        $this->assertTrue($informant->isMusician);

        $informant = $organization->informants[3];
        $this->assertEquals('Краўчук Іван', $informant->name);
        $this->assertEquals(1922, $informant->birth);
        $this->assertEquals('музыкі, скрыпка', $informant->notes);
        $this->assertTrue($informant->isMusician);

        $informant = $organization->informants[4];
        $this->assertEquals('Кавалевіч Мікалай', $informant->name);
        $this->assertEquals(1947, $informant->birth);
        $this->assertEquals('музыкі, бубен, мастацкі свіст', $informant->notes);
        $this->assertTrue($informant->isMusician);
    }
}
