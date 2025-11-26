<?php

declare(strict_types=1);

namespace App\Tests\Parser\VideoKozParser\ItemsSimple;

use App\Entity\Additional\Artist;
use App\Entity\GeoPoint;
use App\Entity\Pack;
use App\Entity\Type\CategoryType;
use App\Parser\VideoKozParser;
use App\Repository\GeoPointRepository;
use App\Repository\PackRepository;
use App\Service\LocationService;
use App\Service\PersonService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VideoKozParserTest extends TestCase
{
    private readonly VideoKozParser $parser;
    private readonly GeoPointRepository|MockObject $geoPointRepository;
    private readonly PackRepository|MockObject $packRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->geoPointRepository = $this->createMock(GeoPointRepository::class);
        $this->packRepository = $this->createMock(PackRepository::class);

        $locationService = new LocationService($this->geoPointRepository);
        $personService = new PersonService();
        $this->parser = new VideoKozParser($locationService, $personService, $this->packRepository);
    }

    public function testParse(): void
    {
        $geoPoint = new GeoPoint('242990503');
        $this->geoPointRepository->expects($this->exactly(3))
            ->method('findByNameAndDistrict')
            ->willReturn([$geoPoint]);

        $pack = new Pack();
        $pack->setName('сольны-тэст');
        $this->packRepository->expects($this->exactly(3))
            ->method('getPackByName')
            ->willReturn($pack);

        $filename = __DIR__ . '/videos.csv';
        $content = file_get_contents($filename);

        $files = $this->parser->parse($content);

        $this->assertCount(3, $files);

        $file = $files[0];
        $this->assertEquals('Іванаўскі\Беразлянскі СК 08 Мікіта.avi', $file->getFilename());
        $this->assertCount(1, $file->videoItems);

        $item = $file->videoItems[0];
        $this->assertEquals(CategoryType::DANCE, $item->category);
        $this->assertEquals('тып Мікіта', $item->baseName);
        $this->assertEquals('Полька на вылка́х', $item->localName);
        $this->assertEquals('aypL7yMMd3AA', $item->youTube);
        $this->assertEquals('сольны-тэст', $item->pack->getName());
        $this->assertEquals('тыпу Мікіта', $item->improvisation);
        $this->assertEquals('#Сямейная абраднасць і паэзія#Вяселле', $item->ritual);
        $this->assertNotNull($item->geoPoint);
        $this->assertEquals(242990503, $item->geoPoint->getId());
        $this->assertNotNull($item->dateAction);
        $this->assertEquals('2003-06-24', $item->dateAction->format('Y-m-d'));
        $this->assertEquals('...у мясцовай традыцыі выконваецца пад найгрыш полькі...', $item->notes);
        $this->assertEquals('Фальклорны калектыў Беразлянскага СДК', $item->organizationName);
        $this->assertCount(2, $item->informants);
        $this->assertEquals('Гетманчук Алена Данілаўна', $item->informants[0]->name);
        $this->assertEquals(1946, $item->informants[0]->birth);
        $this->assertEquals('Якушка Аляксандр Васільевіч', $item->informants[1]->name);
        $this->assertEquals(1956, $item->informants[1]->birth);
        $this->assertEquals('музыкант, баян', $item->informants[1]->notes);
        $this->assertTrue($item->informants[1]->isMusician);
        $this->assertEquals('няма', $item->texts);
        $this->assertEquals('Узгадваецца ў прадмове кнігі Традыцыйная мастацкая культура беларусаў на с. 12 (Беразляны “Мыкыта”).', $item->tmkb);

        $item = $files[1]->videoItems[0];
        $this->assertEquals(CategoryType::STORY, $item->category);
        $this->assertEquals('', $item->baseName);
        $this->assertEquals('', $item->localName);
        $this->assertEquals('Q2ClNDiRzsUA', $item->youTube);
        $this->assertEquals('сольны-тэст', $item->pack->getName());
        $this->assertEquals('', $item->improvisation);
        $this->assertNull($item->dateAction);
        $this->assertEquals('Гутарка з мясцовым калектывам.' . "\n\r" . 'Дата запісу: сяр. 1990-х', $item->notes);
        $this->assertEquals('', $item->organizationName);
        $this->assertCount(3, $item->informants);
        $this->assertEquals('Шурко Галіна Сцяпанаўна', $item->informants[0]->name);
        $this->assertEquals(1938, $item->informants[0]->birth);
        $this->assertEquals('Грыцкевіч Уладзімір Паўлавіч', $item->informants[1]->name);
        $this->assertNull($item->informants[1]->birth);
        $this->assertEquals('барабан', $item->informants[1]->notes);
        $this->assertTrue($item->informants[1]->isMusician);
        $this->assertEquals('Велескевіч Павел Міхайлавіч', $item->informants[2]->name);
        $this->assertEquals(1949, $item->informants[2]->birth);
        $this->assertEquals('баян, маст. кіраўнік', $item->informants[2]->notes);
        $this->assertTrue($item->informants[2]->isMusician);
        $this->assertNotEmpty($item->source);
        $this->assertTrue(Artist::isChildren($item->source));
        $this->assertStringContainsString('Ой чого ты, лысый,', $item->texts);
        $this->assertEquals('', $item->tmkb);

        $item = $files[2]->videoItems[0];
        $this->assertEquals(CategoryType::FILM, $item->category);
        $this->assertEquals('', $item->baseName);
        $this->assertEquals('Нацыянальны фальклор і яго значэнне', $item->localName);
        $this->assertEquals('dW-lYqMlbJw', $item->youTube);
        $this->assertEquals('', $item->improvisation);
        $this->assertEquals('1991-01-01', $item->dateAction->format('Y-m-d'));
        $this->assertEquals('', $item->notes);
        $this->assertEquals('', $item->organizationName);
        $this->assertCount(0, $item->informants);
        $this->assertEmpty($item->source);
        $this->assertEquals('', $item->texts);
        $this->assertEquals('', $item->tmkb);
    }
}
