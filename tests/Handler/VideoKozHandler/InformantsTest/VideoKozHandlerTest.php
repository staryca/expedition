<?php

declare(strict_types=1);

namespace App\Tests\Handler\VideoKozHandler\InformantsTest;

use App\Handler\VideoKozHandler;
use App\Manager\ReportManager;
use App\Parser\VideoKozParser;
use App\Repository\ExpeditionRepository;
use App\Repository\FileMarkerRepository;
use App\Repository\GeoPointRepository;
use App\Repository\PackRepository;
use App\Repository\RitualRepository;
use App\Repository\UserRepository;
use App\Service\LocationService;
use App\Service\PersonService;
use App\Service\RitualService;
use App\Service\YoutubeService;
use PHPUnit\Framework\TestCase;

class VideoKozHandlerTest extends TestCase
{
    private readonly VideoKozHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $geoPointRepository = $this->createMock(GeoPointRepository::class);
        $packRepository = $this->createMock(PackRepository::class);
        $expeditionRepository = $this->createMock(ExpeditionRepository::class);
        $fileMarkerRepository = $this->createMock(FileMarkerRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $ritualRepository = $this->createMock(RitualRepository::class);

        $locationService = new LocationService($geoPointRepository);
        $personService = new PersonService();
        $parser = new VideoKozParser($locationService, $personService, $packRepository);

        $reportManager = $this->createMock(ReportManager::class);
        $ritualService = new RitualService($ritualRepository);
        $youtubeService = $this->createMock(YoutubeService::class);

        $this->handler = new VideoKozHandler(
            $parser,
            $expeditionRepository,
            $fileMarkerRepository,
            $personService,
            $userRepository,
            $reportManager,
            $ritualService,
            $youtubeService
        );
    }

    public function testParse(): void
    {
        $filename = __DIR__ . '/videos.csv';
        $files = $this->handler->checkFile($filename);

        $this->assertCount(3, $files);

        $item = $files[0]->videoItems[0];
        $this->assertCount(2, $item->informants);
        $this->assertEquals('Гетманчук Алена Данілаўна', $item->informants[0]->name);
        $this->assertEquals(1946, $item->informants[0]->birth);
        $this->assertEquals('Якушка Аляксандр Васільевіч', $item->informants[1]->name);
        $this->assertEquals(1956, $item->informants[1]->birth);
        $this->assertEquals('баян', $item->informants[1]->notes);
        $this->assertTrue($item->informants[1]->isMusician);

        $item = $files[1]->videoItems[0];
        $this->assertCount(3, $item->informants);
        $this->assertEquals('Гетманчук Алена Данілаўна', $item->informants[0]->name);
        $this->assertEquals(1946, $item->informants[0]->birth);
        $this->assertEquals('Шуляк Ганна Аляксееўна', $item->informants[1]->name);
        $this->assertEquals(1931, $item->informants[1]->birth);
        $this->assertEquals('', $item->informants[1]->notes);
        $this->assertEquals('Якушка Аляксандр Васільевіч', $item->informants[2]->name);
        $this->assertEquals(1956, $item->informants[2]->birth);
        $this->assertEquals('баян', $item->informants[2]->notes);
        $this->assertTrue($item->informants[2]->isMusician);

        $item = $files[2]->videoItems[0];
        $this->assertCount(7, $item->informants);
        $this->assertEquals('Гетманчук Алена Сцяпанаўна', $item->informants[0]->name);
        $this->assertEquals(1937, $item->informants[0]->birth);
        $this->assertEquals('Гетманчук Алена Данілаўна', $item->informants[1]->name);
        $this->assertEquals(1946, $item->informants[1]->birth);
        $this->assertEquals('Гетманчук Вера Дзмітрыеўна', $item->informants[2]->name);
        $this->assertEquals(1936, $item->informants[2]->birth);
        $this->assertEquals('', $item->informants[2]->notes);
        $this->assertEquals('Луцэвіч Любоў Андрэеўна', $item->informants[3]->name);
        $this->assertEquals(1937, $item->informants[3]->birth);
        $this->assertEquals('Шуляк Ганна Аляксееўна', $item->informants[4]->name);
        $this->assertEquals(1931, $item->informants[4]->birth);
        $this->assertEquals('Шуляк Лідзія Мікалаеўна', $item->informants[5]->name);
        $this->assertEquals(1958, $item->informants[5]->birth);
        $this->assertEquals('Якушка Аляксандр Васільевіч', $item->informants[6]->name);
        $this->assertEquals(1956, $item->informants[6]->birth);
        $this->assertEquals('баян', $item->informants[6]->notes);
        $this->assertTrue($item->informants[6]->isMusician);

        $informants = $this->handler->getInformants($files);

        $this->assertCount(7, $informants);
        $this->assertEquals('Гетманчук Алена Данілаўна', $informants[0]->name);
        $this->assertEquals('Якушка Аляксандр Васільевіч', $informants[1]->name);
        $this->assertEquals('Шуляк Ганна Аляксееўна', $informants[2]->name);
        $this->assertEquals('Гетманчук Алена Сцяпанаўна', $informants[3]->name);
        $this->assertEquals('Гетманчук Вера Дзмітрыеўна', $informants[4]->name);
        $this->assertEquals('Луцэвіч Любоў Андрэеўна', $informants[5]->name);
        $this->assertEquals('Шуляк Лідзія Мікалаеўна', $informants[6]->name);

        $this->assertEquals([0, 1], $files[0]->videoItems[0]->informantKeys);
        $this->assertEquals([0, 2, 1], $files[1]->videoItems[0]->informantKeys);
        $this->assertEquals([3, 0, 4, 5, 2, 6, 1], $files[2]->videoItems[0]->informantKeys);

        $hash0 = $files[0]->videoItems[0]->getHash();
        $hash1 = $files[1]->videoItems[0]->getHash();
        $hash2 = $files[2]->videoItems[0]->getHash();
        $this->assertNotEquals($hash0, $hash1);
        $this->assertNotEquals($hash1, $hash2);
        $this->assertNotEquals($hash0, $hash2);
    }
}
