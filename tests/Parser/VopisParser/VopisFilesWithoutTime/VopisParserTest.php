<?php

declare(strict_types=1);

namespace App\Tests\Parser\VopisParser\VopisFilesWithoutTime;

use App\Entity\Type\CategoryType;
use App\Manager\FileManager;
use App\Parser\VopisParser;
use App\Repository\GeoPointRepository;
use App\Service\LocationService;
use App\Service\PersonService;
use Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VopisParserTest extends TestCase
{
    private readonly VopisParser $vopisParser;
    private readonly GeoPointRepository $geoPointRepository;
    private readonly FileManager $fileManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->geoPointRepository = $this->createMock(GeoPointRepository::class);

        $locationService = new LocationService($this->geoPointRepository);
        $personService = new PersonService();
        $this->vopisParser = new VopisParser($locationService);
        $this->fileManager = new FileManager($personService);
    }

    public function testParse(): void
    {
        $filename = __DIR__ . '/vopis.csv';
        $content = file_get_contents($filename);

        $files = $this->vopisParser->parse($content, false);

        $this->assertCount(2, $files);

        $file = $files[0];
        $this->assertEquals('sn_(dr)_2_A', $file->getFilename());
        $this->assertCount(5, $file->markers);

        $this->assertTrue($file->markers[0]->isNewBlock);
        $this->assertEquals('в. Крывыя, Талачынскі раён', $file->markers[0]->place);
        $this->assertEquals('Конюшка Ганна Маркаўна', $file->markers[0]->informantsText);

        $this->assertFalse($file->markers[1]->isNewBlock);
        $this->assertEquals('"яно пры мне раджалася"', $file->markers[1]->name);
        $this->assertEquals(CategoryType::SONGS, $file->markers[1]->category);
        $this->assertEquals('словы жніўнай песьні (Я жала - зажалася)', $file->markers[1]->notes);

        $this->assertFalse($file->markers[2]->isNewBlock);
        $this->assertEquals('"А я ў полі жыта жала"', $file->markers[2]->name);
        $this->assertEquals(CategoryType::SONGS, $file->markers[2]->category);
        $this->assertEquals('жніво - словы', $file->markers[2]->notes);

        $this->assertFalse($file->markers[3]->isNewBlock);
        $this->assertEquals('"Калядачкі, бліны-ладачкі"', $file->markers[3]->name);
        $this->assertEquals(CategoryType::SONGS, $file->markers[3]->category);

        $this->assertFalse($file->markers[4]->isNewBlock);
        $this->assertEquals('[Звычаі на Вялікдзень]', $file->markers[4]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[4]->category);

        $file = $files[1];
        $this->assertEquals('sn_(dr)_2_B', $file->getFilename());
        $this->assertCount(3, $file->markers);

        $this->assertFalse($file->markers[0]->isNewBlock);
        $this->assertEquals('[Пра чараўніка]', $file->markers[0]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[0]->category);
        $this->assertEquals('', $file->markers[0]->notes);

        $this->assertFalse($file->markers[1]->isNewBlock);
        $this->assertEquals('', $file->markers[1]->name);
        $this->assertEquals(CategoryType::OTHER, $file->markers[1]->category);
        $this->assertEquals('Вясельны абрад', $file->markers[1]->notes);
        $this->assertEquals('', $file->markers[1]->informantsText);
    }

    public function testReports(): void
    {
        $filename = __DIR__ . '/vopis.csv';
        $content = file_get_contents($filename);

        $files = $this->vopisParser->parse($content, false);
        $reports = $this->fileManager->createReports($files, Carbon::now());

        $this->assertCount(1, $reports);

        $this->assertCount(1, $reports[0]->blocks);
        $this->assertEquals('в. Крывыя, Талачынскі раён', $reports[0]->place);
        $this->assertCount(1, $reports[0]->blocks[0]->informants);
        $this->assertEquals('Конюшка Ганна Маркаўна', $reports[0]->blocks[0]->informants[0]->name);
    }
}
