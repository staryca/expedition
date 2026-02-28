<?php

declare(strict_types=1);

namespace App\Tests\Parser\ImefParser;

use App\Dto\ImefDto;
use App\Entity\Type\CategoryType;
use App\Parser\ImefParser;
use App\Repository\GeoPointRepository;
use App\Service\LocationService;
use App\Service\PersonService;
use PHPUnit\Framework\TestCase;

class ImefParserTest extends TestCase
{
    private readonly ImefParser $parser;

    public function setUp(): void
    {

        $geoPointRepository = $this->createMock(GeoPointRepository::class);

        $locationService = new LocationService($geoPointRepository);
        $personService = new PersonService();

        $this->parser = new ImefParser($locationService, $personService);
    }

    public function testParse(): void
    {
        $filename = __DIR__ . '/content.html';
        $content = file_get_contents($filename);

        $previousDateDayMonth = true;
        $dtos = $this->parser->parseItem($previousDateDayMonth, $content);
        $this->assertCount(29, $dtos);

        /** @var ImefDto $dto */
        $dto = array_shift($dtos);
        $this->assertEquals(1973, $dto->date->year);
        $this->assertCount(1, $dto->users);
        $this->assertEquals('Бахмет Святлана Трафімаўна', $dto->users[0]->name);
        $this->assertEquals('Асіповіцкі раён, в. Зборск', $dto->place);
        $this->assertCount(1, $dto->informants);
        $this->assertEquals('Сацэвіч Аўгіня Цітава', $dto->informants[0]->name);
        $this->assertEquals(1885, $dto->informants[0]->birth);
        $this->assertEquals('Ты дубочак зеляненькі', $dto->name);
        $this->assertCount(1, $dto->tags);
        $this->assertEquals('Лірычныя песні', $dto->tags[0]);
        $this->assertEquals(CategoryType::SONGS, $dto->category);

        /** @var ImefDto $dto */
        $dto = array_shift($dtos);
        $this->assertEquals('19830615', $dto->date->format('Ymd'));
        $this->assertCount(1, $dto->users);
        $this->assertEquals('Бахмет Святлана Трафімаўна', $dto->users[0]->name);
        $this->assertEquals('Асіповіцкі раён, в. Зборск', $dto->place);
        $this->assertCount(1, $dto->informants);
        $this->assertEquals('Чыгілейчык Марыя Рыгораўна', $dto->informants[0]->name);
        $this->assertEquals(1897, $dto->informants[0]->birth);
        $this->assertEquals('Вох ты сонца, вох ты яснае', $dto->name);
        $this->assertCount(1, $dto->tags);
        $this->assertEquals('Лірычныя песні', $dto->tags[0]);
        $this->assertEquals(CategoryType::SONGS, $dto->category);

        /** @var ImefDto $dto */
        $dto = array_shift($dtos);
        $this->assertEquals('19720712', $dto->date->format('Ymd'));
        $this->assertCount(1, $dto->users);
        $this->assertEquals('Бахмет Святлана Трафімаўна', $dto->users[0]->name);
        $this->assertEquals('Асіповіцкі раён, в. Бірча-2', $dto->place);
        $this->assertCount(2, $dto->informants);
        $this->assertEquals('Ермакова Праскоўя Якаўлеўна', $dto->informants[0]->name);
        $this->assertEquals(1907, $dto->informants[0]->birth);
        $this->assertEquals('Карабанава Агапа Якаўлеўна', $dto->informants[1]->name);
        $this->assertEquals(1914, $dto->informants[1]->birth);
        $this->assertEquals('Зялёная вішня з-пад кораня вышла', $dto->name);
        $this->assertCount(2, $dto->tags);
        $this->assertEquals('Лірычныя песні', $dto->tags[0]);
        $this->assertEquals(CategoryType::SONGS, $dto->category);

        /** @var ImefDto $dto */
        $dto = array_shift($dtos);
        $this->assertEquals('19730120', $dto->date->format('Ymd'));
        $this->assertCount(1, $dto->users);
        $this->assertEquals('Бахмет Святлана Трафімаўна', $dto->users[0]->name);
        $this->assertEquals('Асіповіцкі раён, в. Дрычын', $dto->place);
        $this->assertCount(1, $dto->informants);
        $this->assertEquals('Бовіч Акуліна Адамаўна', $dto->informants[0]->name);
        $this->assertEquals(1927, $dto->informants[0]->birth);
        $this->assertEquals('працуе ў КБО', $dto->informants[0]->notes);
        $this->assertEquals('Да гарэлкі, сваткі, гарэлкі', $dto->name);
        $this->assertCount(2, $dto->tags);
        $this->assertEquals('Сямейная абраднасць і паэзія', $dto->tags[0]);
        $this->assertEquals(CategoryType::OTHER, $dto->category);

        /** @var ImefDto $dto */
        $dto = array_shift($dtos);
        $this->assertNull($dto->date);
        $this->assertCount(1, $dto->users);
        $this->assertEquals('Бахмет Святлана Трафімаўна', $dto->users[0]->name);
        $this->assertEquals('Асіповіцкі раён, в. Дрычын', $dto->place);
        $this->assertCount(3, $dto->informants);
        $this->assertEquals('Пышная Яўгенія Ільінічна', $dto->informants[0]->name);
        $this->assertEquals(1911, $dto->informants[0]->birth);
        $this->assertEquals('', $dto->informants[0]->notes);
        $this->assertEquals('Кашыцкая Матруна Аляксееўна', $dto->informants[1]->name);
        $this->assertEquals(1909, $dto->informants[1]->birth);
        $this->assertEquals('Пышная Прыя Аляксееўна', $dto->informants[2]->name);
        $this->assertEquals(1912, $dto->informants[2]->birth);
        $this->assertEquals('Ды я жала, не лягала', $dto->name);
        $this->assertCount(4, $dto->tags);
        $this->assertEquals('Каляндарная абраднасць і паэзія', $dto->tags[0]);
        $this->assertEquals(CategoryType::OTHER, $dto->category);

        /** @var ImefDto $dto */
        $dto = array_shift($dtos);
        $this->assertNull($dto->date);
        $this->assertCount(2, $dto->users);
        $this->assertEquals('Бахмет Святлана Трафімаўна', $dto->users[0]->name);
        $this->assertEquals('Акунькова Алена Міхайлаўна', $dto->users[1]->name);
        $this->assertEquals('Бабруйскі раён, в. Тажылавічы', $dto->place);
        $this->assertCount(2, $dto->informants);
        $this->assertEquals('Харанека Хрысціна Піліпаўна', $dto->informants[0]->name);
        $this->assertEquals(1905, $dto->informants[0]->birth);
        $this->assertEquals('', $dto->informants[0]->notes);
        $this->assertEquals('Мельнічонак Варвара Пятроўна', $dto->informants[1]->name);
        $this->assertEquals(1916, $dto->informants[1]->birth);
        $this->assertEquals('А пад дубам, дубам ячмень', $dto->name);
        $this->assertCount(2, $dto->tags);
        $this->assertEquals('Каляндарная абраднасць і паэзія', $dto->tags[0]);
        $this->assertEquals(CategoryType::OTHER, $dto->category);

        /** @var ImefDto $dto */
        $dto = array_shift($dtos);
        $this->assertEquals('19750919', $dto->date->format('Ymd'));
        $this->assertCount(2, $dto->users);
        $this->assertEquals('Бахмет Святлана Трафімаўна', $dto->users[0]->name);
        $this->assertEquals('Акунькова Алена Міхайлаўна', $dto->users[1]->name);
        $this->assertEquals('Бабруйскі раён, в. Тажылавічы', $dto->place);
        $this->assertCount(1, $dto->informants);
        $this->assertEquals('Гацко ... Дзмітрыеўна', $dto->informants[0]->name);
        $this->assertEquals('', $dto->informants[0]->notes);
        $this->assertNull($dto->informants[0]->birth);
        $this->assertEquals('', $dto->informants[0]->notes);
        $this->assertEquals('Каліна-маліна |Край мора стаяла…', $dto->name);
        $this->assertCount(1, $dto->tags);
        $this->assertEquals('Лірычныя песні', $dto->tags[0]);
        $this->assertEquals(CategoryType::SONGS, $dto->category);

        /** @var ImefDto $dto */
        $dto = array_shift($dtos);
        $this->assertEquals('19750627', $dto->date->format('Ymd'));
        $this->assertCount(1, $dto->users);
        $this->assertEquals('Меяровіч Марыя Самуілаўна', $dto->users[0]->name);
        $this->assertEquals('Бабруйскі раён, Плешчаніцы', $dto->place);
        $this->assertCount(1, $dto->informants);
        $this->assertEquals('Шуба-Мельнікаў Уладзімір Мікалаевіч', $dto->informants[0]->name);
        $this->assertEquals('', $dto->informants[0]->notes);
        $this->assertNull($dto->informants[0]->birth);
        $this->assertEquals('', $dto->informants[0]->notes);
        $this->assertEquals('Мы йшлі на дзела ночкаю цёмнаю…', $dto->name);
        $this->assertCount(2, $dto->tags);
        $this->assertEquals('Лірычныя песні', $dto->tags[0]);
        $this->assertEquals(CategoryType::SONGS, $dto->category);

        /** @var ImefDto $dto */
        $dto = array_shift($dtos);
        $this->assertEquals('19460818', $dto->date->format('Ymd'));
        $this->assertCount(2, $dto->users);
        $this->assertEquals('Бахмет Святлана Трафімаўна', $dto->users[0]->name);
        $this->assertEquals('Бабруйскі раён, в. Тажылавічы', $dto->place);
        $this->assertCount(1, $dto->informants);
        $this->assertEquals('Гацко ... Дзмітрыеўна', $dto->informants[0]->name);
        $this->assertEquals('', $dto->informants[0]->notes);
        $this->assertNull($dto->informants[0]->birth);
        $this->assertEquals('', $dto->informants[0]->notes);
        $this->assertEquals('Я ля чаю хадзіла', $dto->name);
        $this->assertCount(1, $dto->tags);
        $this->assertEquals('Лірычныя песні', $dto->tags[0]);
        $this->assertEquals(CategoryType::SONGS, $dto->category);

        /** @var ImefDto $dto */
        $dto = array_shift($dtos);
        $this->assertEquals('19610630', $dto->date->format('Ymd'));
        $this->assertCount(2, $dto->users);
        $this->assertEquals('Бахмет Святлана Трафімаўна', $dto->users[0]->name);
        $this->assertEquals('Бабруйскі раён, в. Тажылавічы', $dto->place);
        $this->assertCount(1, $dto->informants);
        $this->assertEquals('Гацко ... Дзмітрыеўна', $dto->informants[0]->name);
        $this->assertEquals('', $dto->informants[0]->notes);
        $this->assertNull($dto->informants[0]->birth);
        $this->assertEquals('', $dto->informants[0]->notes);
        $this->assertEquals('Да расла да цвіла да чарэмуха', $dto->name);
        $this->assertCount(2, $dto->tags);
        $this->assertEquals('Вясна', $dto->tags[1]);
        $this->assertEquals(CategoryType::OTHER, $dto->category);
    }
}
