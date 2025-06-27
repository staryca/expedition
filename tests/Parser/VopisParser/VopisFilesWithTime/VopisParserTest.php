<?php

declare(strict_types=1);

namespace App\Tests\Parser\VopisParser\VopisFilesWithTime;

use App\Entity\Type\CategoryType;
use App\Helper\TextHelper;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->geoPointRepository = $this->createMock(GeoPointRepository::class);

        $textHelper = new TextHelper();
        $locationService = new LocationService($this->geoPointRepository, $textHelper);
        $personService = new PersonService($textHelper);
        $this->vopisParser = new VopisParser($locationService, $personService);
    }

    public function testParse(): void
    {
        $filename = __DIR__ . '/vopis.csv';
        $content = file_get_contents($filename);

        $files = $this->vopisParser->parse($content, true);

        $this->assertCount(2, $files);

        $file = $files[0];
        $this->assertEquals('1998.01 Lepel.(I)-2-a.wav', $file->getFilename());
        $this->assertCount(10, $file->markers);

        $this->assertEquals('00:08.381', $file->markers[0]->timeFrom);
        $this->assertEquals('00:19.695', $file->markers[0]->timeTo);
        $this->assertTrue($file->markers[0]->isNewBlock);
        $this->assertEquals('', $file->markers[0]->name);
        $this->assertEquals(CategoryType::OTHER, $file->markers[0]->category);
        $this->assertEquals('Запіс у в. Адамаўка (зацёр кавалак папярэдняга запісу ў в.Сталюгі)', $file->markers[0]->notes);
        $this->assertEquals('в. Адамаўка, Лепельскі р-н', $file->markers[0]->place);
        $this->assertEquals('', $file->markers[0]->informantsText);

        $this->assertEquals('00:19.695', $file->markers[1]->timeFrom);
        $this->assertEquals('02:47.197', $file->markers[1]->timeTo);
        $this->assertFalse($file->markers[1]->isNewBlock);
        $this->assertEquals('(Пра Сівы камень ля в.Адамаўка)', $file->markers[1]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[1]->category);

        $this->assertTrue($file->markers[2]->isNewBlock);
        $this->assertEquals('', $file->markers[2]->name);
        $this->assertEquals(CategoryType::OTHER, $file->markers[2]->category);
        $this->assertEquals('Іншы інфарматар, в.Адамаўка', $file->markers[2]->notes);
        $this->assertEquals('в. Адамаўка, Лепельскі р-н', $file->markers[2]->place);

        $this->assertFalse($file->markers[3]->isNewBlock);
        $this->assertEquals('(Пра Сівы камень)', $file->markers[3]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[3]->category);

        $this->assertTrue($file->markers[4]->isNewBlock);
        $this->assertEquals('', $file->markers[4]->name);
        $this->assertEquals(CategoryType::OTHER, $file->markers[4]->category);
        $this->assertEquals('Запіс у в. Сталюгі', $file->markers[4]->notes);
        $this->assertEquals('в. Сталюгі, Лепельскі р-н', $file->markers[4]->place);

        $this->assertFalse($file->markers[5]->isNewBlock);
        $this->assertEquals('... Не пытай мамка жыцьця майго', $file->markers[5]->name);
        $this->assertEquals(CategoryType::SONGS, $file->markers[5]->category);
        $this->assertEquals('песьня, канец', $file->markers[5]->notes);

        $this->assertFalse($file->markers[8]->isNewBlock);
        $this->assertEquals('', $file->markers[8]->name);
        $this->assertEquals(CategoryType::ABOUT_RECORD, $file->markers[8]->category);
        $this->assertEquals('', $file->markers[8]->notes);

        $this->assertEquals('09:55.701', $file->markers[9]->timeFrom);
        $this->assertNull($file->markers[9]->timeTo);
        $this->assertFalse($file->markers[9]->isNewBlock);
        $this->assertEquals('', $file->markers[9]->name);
        $this->assertEquals(CategoryType::ABOUT_INFORMANT, $file->markers[9]->category);
        $this->assertEquals('', $file->markers[9]->notes);

        $file = $files[1];
        $this->assertEquals('1998.01 Lepel.(I)-3-a.wav', $file->getFilename());
        $this->assertCount(11, $file->markers);

        $this->assertEquals('00:08.453', $file->markers[0]->timeFrom);
        $this->assertEquals('00:08.876', $file->markers[0]->timeTo);
        $this->assertTrue($file->markers[0]->isNewBlock);
        $this->assertEquals('', $file->markers[0]->name);
        $this->assertEquals(CategoryType::OTHER, $file->markers[0]->category);
        $this->assertEquals('Запіс у в. Сялец', $file->markers[0]->notes);
        $this->assertEquals('в. Сялец, Лепельскі р-н', $file->markers[0]->place);
        $this->assertEquals('', $file->markers[0]->informantsText);

        $this->assertTrue($file->markers[4]->isNewBlock);
        $this->assertEquals('', $file->markers[4]->name);
        $this->assertEquals(CategoryType::OTHER, $file->markers[4]->category);
        $this->assertEquals('Запіс ад Яніны Чарнухі з мужам', $file->markers[4]->notes);
        $this->assertEquals('Яніна Чарнуха; муж яе', $file->markers[4]->informantsText);

        $this->assertEquals('08:02.383', $file->markers[8]->timeFrom);
        $this->assertEquals('10:24.951', $file->markers[8]->timeTo);
    }

    public function testReports(): void
    {
        $filename = __DIR__ . '/vopis.csv';
        $content = file_get_contents($filename);

        $files = $this->vopisParser->parse($content, true);
        $reports = $this->vopisParser->createReports($files, Carbon::now());

        $this->assertCount(4, $reports);

        $this->assertCount(2, $reports[0]->blocks);
        $this->assertEquals('в. Адамаўка, Лепельскі р-н', $reports[0]->place);
        $this->assertCount(0, $reports[0]->blocks[0]->informants);
        $this->assertCount(0, $reports[0]->blocks[1]->informants);

        $this->assertCount(1, $reports[1]->blocks);
        $this->assertEquals('в. Сталюгі, Лепельскі р-н', $reports[1]->place);
        $this->assertCount(0, $reports[1]->blocks[0]->informants);

        $this->assertCount(1, $reports[2]->blocks);
        $this->assertEquals('в. Сялец, Лепельскі р-н', $reports[2]->place);
        $this->assertCount(0, $reports[2]->blocks[0]->informants);

        $this->assertCount(1, $reports[3]->blocks);
        $this->assertEquals('', $reports[3]->place);
        $this->assertCount(2, $reports[3]->blocks[0]->informants);
        $this->assertEquals('Чарнуха Яніна', $reports[3]->blocks[0]->informants[0]->name);
        $this->assertEquals('муж яе', $reports[3]->blocks[0]->informants[1]->name);
    }
}
