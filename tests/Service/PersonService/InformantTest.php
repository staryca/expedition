<?php

declare(strict_types=1);

namespace App\Tests\Service\PersonService;

use App\Entity\Type\GenderType;
use App\Helper\TextHelper;
use App\Service\PersonService;
use PHPUnit\Framework\TestCase;

class InformantTest extends TestCase
{
    private readonly PersonService $personService;

    public function setUp(): void
    {
        parent::setUp();

        $textHelper = new TextHelper();
        $this->personService = new PersonService($textHelper);
    }

    public function testInformantSimple(): void
    {
        $content = 'Марозаў Анатолій Уладзіміравіч, 1958, в. Якубава';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Марозаў Анатолій Уладзіміравіч', $informant->name);
        $this->assertEquals(1958, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Якубава', $informant->locations[0]);
    }

    public function testInformantWithNotes(): void
    {
        $content = 'Барадулькіна (Жукава) Марыя Сьцяпанаўна, 1928 (але сказала, што ёй 96 год), хутар Пакава';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Барадулькіна (Жукава) Марыя Сьцяпанаўна', $informant->name);
        $this->assertEquals(1928, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('але сказала, што ёй 96 год', $informant->notes);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('х. Пакава', $informant->locations[0]);
    }

    public function testInformantWithLastNotes(): void
    {
        $content = 'Лужанскі Барыс Іванавіч, 1961, Адзеская вобл. Украіна, зяць Трашчанка Я.У.';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Лужанскі Барыс Іванавіч', $informant->name);
        $this->assertEquals(1961, $informant->birth);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('Адзеская вобл. Украіна', $informant->locations[0]);
        $this->assertEquals('зяць Трашчанка Я.У.', $informant->notes);
    }


    public function testInformantWithoutLocation(): void
    {
        $content = 'Ніна Іванаўна Фядзькова (Кавалёва), 1939, (жонка Федзькоў Уладзімір Іванавіч)';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Фядзькова (Кавалёва) Ніна Іванаўна', $informant->name);
        $this->assertEquals(1939, $informant->birth);
        $this->assertCount(0, $informant->locations);
        $this->assertEquals('жонка Федзькоў Уладзімір Іванавіч', $informant->notes);
    }

    public function testInformantWithYearBirth(): void
    {
        $content = 'Зінаіда Аляксееўна Гарбатава (Конанава), 1941 г.н., Віцебск, стараверка (“маскоўка”)';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Гарбатава (Конанава) Зінаіда Аляксееўна', $informant->name);
        $this->assertEquals(1941, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('стараверка, “маскоўка”', $informant->notes);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('Віцебск', $informant->locations[0]);
    }

    public function testInformantWithYearReport(): void
    {
        $content = 'Сацэвіч Аўгіня Цітава, 88 год';

        $informants = $this->personService->getInformants($content, '', null, 1990);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Сацэвіч Аўгіня Цітава', $informant->name);
        $this->assertEquals(1902, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
        $this->assertCount(0, $informant->locations);
    }

    public function testInformantWithShortYearReport(): void
    {
        $content = 'Андрыеўскі Фама Антонавіч, 86 г.';

        $informants = $this->personService->getInformants($content, '', null, 2010);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Андрыеўскі Фама Антонавіч', $informant->name);
        $this->assertEquals(1924, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
        $this->assertCount(0, $informant->locations);
    }

    public function testInformantWithYearWithoutComma(): void
    {
        $content = 'Чыгілейчык Марыя Рыгораўна 1897 г.н';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Чыгілейчык Марыя Рыгораўна', $informant->name);
        $this->assertEquals(1897, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
        $this->assertCount(0, $informant->locations);
    }

    public function testInformantWithYearReportY(): void
    {
        $content = 'Пышная Уляна Рыгораўна, 73 гады';

        $informants = $this->personService->getInformants($content, '', null, 1990);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Пышная Уляна Рыгораўна', $informant->name);
        $this->assertEquals(1917, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
        $this->assertCount(0, $informant->locations);
    }

    public function testInformantWithManyInfo(): void
    {
        $content = 'Раманоўская Агапа Савічна 85 год, в. Кашэвічы, Петрыкаўскага раёна Гомельскай вобласці';

        $informants = $this->personService->getInformants($content, '', null, 2000);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Раманоўская Агапа Савічна', $informant->name);
        $this->assertEquals(1915, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Кашэвічы, Петрыкаўскага раёна Гомельскай вобласці', $informant->locations[0]);
    }

    public function testInformantWithManyInfoAndNotes(): void
    {
        $content = 'Тагай Вольга Астапаўна, 1914 г.эн., пенсіянерка, в. Кашэвічы, Петрыкаўскага раёна, Гомельскай вобласці';

        $informants = $this->personService->getInformants($content, '', null, 2000);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Тагай Вольга Астапаўна', $informant->name);
        $this->assertEquals(1914, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('пенсіянерка', $informant->notes);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Кашэвічы, Петрыкаўскага раёна, Гомельскай вобласці', $informant->locations[0]);
    }

    public function testInformantWithLocation(): void
    {
        $content = 'Ніна Яўсееўна Хомчанка (Жукава), 1937 г.н., з в. Грышына';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Хомчанка (Жукава) Ніна Яўсееўна', $informant->name);
        $this->assertEquals(1937, $informant->birth);
        $this->assertEquals('', $informant->notes);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Грышына', $informant->locations[0]);
    }

    public function testTwoInformants(): void
    {
        $content = 'Мікалай Мікалаевіч Кутузаў, 1926 г.н., з в. Дудкі, былы ўрач заборскай бальніцы;
            Валянціна Васільеўна Каршкова, 1945 г.н., з в. Шылава, жонка (другая).  Забор’е, Інтэрнацыянальная, 37';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(2, $informants);

        $informant = $informants[0];
        $this->assertEquals('Кутузаў Мікалай Мікалаевіч', $informant->name);
        $this->assertEquals(1926, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('былы ўрач заборскай бальніцы', $informant->notes);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Дудкі', $informant->locations[0]);

        $informant = $informants[1];
        $this->assertEquals('Каршкова Валянціна Васільеўна', $informant->name);
        $this->assertEquals(1945, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('жонка, другая, Забор’е, Інтэрнацыянальная, 37', $informant->notes);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Шылава', $informant->locations[0]);
    }

    public function testInformantWithDayBirth(): void
    {
        $content = 'Кандрацьеў Франц Францавіч, 28.04.1928, в. Грыбова';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Кандрацьеў Франц Францавіч', $informant->name);
        $this->assertEquals(1928, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('1928-04-28', $informant->birthDay->format('Y-m-d'));
        $this->assertEquals('', $informant->notes);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Грыбова', $informant->locations[0]);
    }

    public function testInformantWithMinus(): void
    {
        $content = 'Мацкевіч-Забэла Вольга Ўладзіміраўна';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Мацкевіч-Забэла Вольга Ўладзіміраўна', $informant->name);
        $this->assertNull($informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
        $this->assertCount(0, $informant->locations);
    }

    public function testInformantBadName(): void
    {
        $content = 'Плотка МарыЯ ЯкаўлеўнА, 61 г. (1885 г.н.)';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(1, $informants);
        $informant = $informants[0];
        $this->assertEquals('Плотка Марыя Якаўлеўна', $informant->name);
        $this->assertEquals(1885, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('61 г.', $informant->notes);
        $this->assertCount(0, $informant->locations);
    }

    public function testInformantWithShortName(): void
    {
        $content = 'Сцешыч Мар’і Іллюн(?)., 40 г., Пляшкевіч Алесі Паўл., 32 г., не гр., і Рудзеня Мар’і Васіл., 28 г.,';

        $informants = $this->personService->getInformants($content, '', null, 1980);

        $this->assertCount(3, $informants);

        $informant = $informants[0];
        $this->assertEquals('Сцешыч Мар\'я Іллюн (?)', $informant->name);
        $this->assertEquals(1940, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
        $this->assertCount(0, $informant->locations);

        $informant = $informants[1];
        $this->assertEquals('Пляшкевіч Алеся Паўл.', $informant->name);
        $this->assertEquals(1948, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('не гр.', $informant->notes);
        $this->assertCount(0, $informant->locations);

        $informant = $informants[2];
        $this->assertEquals('Рудзеня Мар\'я Васіл.', $informant->name);
        $this->assertEquals(1952, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
        $this->assertCount(0, $informant->locations);
    }

    public function testTwoInformantsPlus(): void
    {
        $content = 'Марыя Сукач, 1970 г.н. + Мацкевіч Іван Лукіч, 1913 г.н.';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(2, $informants);

        $informant = $informants[0];
        $this->assertEquals('Сукач Марыя', $informant->name);
        $this->assertEquals(1970, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);

        $informant = $informants[1];
        $this->assertEquals('Мацкевіч Іван Лукіч', $informant->name);
        $this->assertEquals(1913, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
    }

    public function testInformantKozManyComma(): void
    {
        $content = 'Алена Сцяпанаўна Гетманчук, 1937 г.н., Алена Данілаўна Гетманчук, 1946 г.н.,
            Вера Гетманчук, 1936 г.н., Любоў Луцэвіч, 1937 г.н., Ганна Шуляк, 1931 г.н., Лідзія Мікалаеўна Шуляк. ';

        $informants = $this->personService->getInformants($content);

        $this->assertCount(6, $informants);

        $informant = $informants[0];
        $this->assertEquals('Гетманчук Алена Сцяпанаўна', $informant->name);
        $this->assertEquals(1937, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);

        $informant = $informants[1];
        $this->assertEquals('Гетманчук Алена Данілаўна', $informant->name);
        $this->assertEquals(1946, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);

        $informant = $informants[2];
        $this->assertEquals('Гетманчук Вера', $informant->name);
        $this->assertEquals(1936, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);

        $informant = $informants[3];
        $this->assertEquals('Луцэвіч Любоў', $informant->name);
        $this->assertEquals(1937, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);

        $informant = $informants[4];
        $this->assertEquals('Шуляк Ганна', $informant->name);
        $this->assertEquals(1931, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);

        $informant = $informants[5];
        $this->assertEquals('Шуляк Лідзія Мікалаеўна', $informant->name);
        $this->assertNull($informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
    }

    public function testInformantKozManyCommaWithNotes(): void
    {
        $content = 'Лідзія Мікалаеўна Шуляк,
            1958 г.н., музыкант Якушка Аляксандр Васільевіч, 1956 г.н. (баян).';

        $informants = $this->personService->getInformants($content);
        $this->assertCount(2, $informants);

        $informant = $informants[0];
        $this->assertEquals('Шуляк Лідзія Мікалаеўна', $informant->name);
        $this->assertEquals(1958, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
        $this->assertNull($informant->isMusician);

        $informant = $informants[1];
        $this->assertEquals('Якушка Аляксандр Васільевіч', $informant->name);
        $this->assertEquals(1956, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('музыкант, баян', $informant->notes);
        $this->assertTrue($informant->isMusician);
    }

    public function testInformantKoz2WithIAndAdditional(): void
    {
        $content = 'Васіль Кавалевіч, 1920 г.н. і Вольга Карагода, 1927 г.н. (баян)';

        $informants = $this->personService->getInformants($content, 'музыкі');
        $this->assertCount(2, $informants);

        $informant = $informants[0];
        $this->assertEquals('Кавалевіч Васіль', $informant->name);
        $this->assertEquals(1920, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('музыкі', $informant->notes);

        $informant = $informants[1];
        $this->assertEquals('Карагода Вольга', $informant->name);
        $this->assertEquals(1927, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('музыкі, баян', $informant->notes);
    }

    public function testInformantKoz2WithIWithoutYears(): void
    {
        $content = 'Галаўчук Надзея Аляксандраўна і Цэбрык Кацярына Фёдараўна';

        $informants = $this->personService->getInformants($content);
        $this->assertCount(2, $informants);

        $informant = $informants[0];
        $this->assertEquals('Галаўчук Надзея Аляксандраўна', $informant->name);
        $this->assertNull($informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);

        $informant = $informants[1];
        $this->assertEquals('Цэбрык Кацярына Фёдараўна', $informant->name);
        $this->assertNull($informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
    }

    public function testInformantKoz1BadYear(): void
    {
        $content = 'Кацярына Мікалаеўна Мазько, 19.. г.н.';

        $informants = $this->personService->getInformants($content);
        $this->assertCount(1, $informants);

        $informant = $informants[0];
        $this->assertEquals('Мазько Кацярына Мікалаеўна', $informant->name);
        $this->assertNull($informant->birth);

        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('19.. г.н.', $informant->notes);
    }

    public function testInformantsWithBrackets(): void
    {
        $content = 'Іван Іосіфавіч Мацкевіч, 1922 г.н., Марыя Ігнацьеўна (жонка), 1923 г.н., Ванда Іванаўна (дачка) 1957 г.н.';

        $informants = $this->personService->getInformants($content);
        $this->assertCount(3, $informants);

        $informant = $informants[0];
        $this->assertEquals('Мацкевіч Іван Іосіфавіч', $informant->name);
        $this->assertEquals(1922, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('', $informant->notes);

        $informant = $informants[1];
        $this->assertEquals('Марыя Ігнацьеўна', $informant->name);
        $this->assertEquals(1923, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('жонка', $informant->notes);

        $informant = $informants[2];
        $this->assertEquals('Ванда Іванаўна', $informant->name);
        $this->assertEquals(1957, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('дачка', $informant->notes);
    }

    public function testInformant2Ages(): void
    {
        $content = 'Федар., 50 г. (1896 г.н.)';

        $informants = $this->personService->getInformants($content);
        $this->assertCount(1, $informants);

        $informant = $informants[0];
        $this->assertEquals('Федар', $informant->name);
        $this->assertEquals(1896, $informant->birth);

        $this->assertEquals(GenderType::UNKNOWN, $informant->gender);
        $this->assertEquals('50 г.', $informant->notes);
    }

    public function testInformantShortMiddleName(): void
    {
        $content = 'Макарэвіч Жэня Міх., 18 г.,(1928 г.н.)';

        $informants = $this->personService->getInformants($content);
        $this->assertCount(1, $informants);

        $informant = $informants[0];
        $this->assertEquals('Жэня Міх. Макарэвіч', $informant->name);
        $this->assertEquals(1928, $informant->birth);

        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('18 г.', $informant->notes);
    }

    public function testInformantWithoutName(): void
    {
        $content = 'Конюшка Ганна Маркаўна; муж яе';

        $informants = $this->personService->getInformants($content);
        $this->assertCount(2, $informants);

        $informant = $informants[0];
        $this->assertEquals('Конюшка Ганна Маркаўна', $informant->name);
        $this->assertNull($informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);

        $informant = $informants[1];
        $this->assertEquals('муж яе', $informant->name);
        $this->assertNull($informant->birth);
        $this->assertEquals(GenderType::UNKNOWN, $informant->gender);
        $this->assertEquals('', $informant->notes);
    }

    public function testInformantLongLocation(): void
    {
        $content = 'Куляшова Ніна Іванаўна, 1939, в. Доўгая Дубрава Хоцімскі р-н';

        $informants = $this->personService->getInformants($content);
        $this->assertCount(1, $informants);

        $informant = $informants[0];
        $this->assertEquals('Куляшова Ніна Іванаўна', $informant->name);
        $this->assertEquals(1939, $informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Доўгая Дубрава Хоцімскі р-н', $informant->locations[0]);
    }

    public function testInformantWithoutYear(): void
    {
        $content = 'Гарохава Ніна Іванаўна, в. Зацесце Клетнянскі р-н Бранская вобласць, спявачка';

        $informants = $this->personService->getInformants($content);
        $this->assertCount(1, $informants);

        $informant = $informants[0];
        $this->assertEquals('Гарохава Ніна Іванаўна', $informant->name);
        $this->assertNull($informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('спявачка', $informant->notes);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Зацесце Клетнянскі р-н Бранская вобласць', $informant->locations[0]);
    }

    public function testInformantWithEachNotes(): void
    {
        $content = 'Дарошка Аляксей Іванавіч,1944 г.н. (баян), Крук Аляксей Аляксандравіч, 1959 г.н. (бубен) ';

        $informants = $this->personService->getInformants($content);
        $this->assertCount(2, $informants);

        $informant = $informants[0];
        $this->assertEquals('Дарошка Аляксей Іванавіч', $informant->name);
        $this->assertEquals(1944, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('баян', $informant->notes);

        $informant = $informants[1];
        $this->assertEquals('Крук Аляксей Аляксандравіч', $informant->name);
        $this->assertEquals(1959, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('бубен', $informant->notes);
    }
}
