<?php

declare(strict_types=1);

namespace App\Tests\Parser\VopisDetailedParser\VopisWithoutTime;

use App\Dto\FileMarkerDto;
use App\Entity\Type\CategoryType;
use App\Parser\VopisDetailedParser;
use App\Repository\GeoPointRepository;
use App\Service\LocationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VopisParserTest extends TestCase
{
    private readonly VopisDetailedParser $parser;
    private readonly GeoPointRepository $geoPointRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->geoPointRepository = $this->createMock(GeoPointRepository::class);

        $locationService = new LocationService($this->geoPointRepository);
        $this->parser = new VopisDetailedParser($locationService);
    }

    public function testParse(): void
    {
        $filename = __DIR__ . '/vopis.csv';
        $content = file_get_contents($filename);

        $subjects = $this->parser->parse($content);

        $this->assertCount(2, $subjects);

        $subject = $subjects[0];
        $this->assertEquals('Стужка 1971-01', $subject->name);
        $this->assertCount(1, $subject->files);
        $file = $subject->files[0];
        $this->assertEquals('Бок ІІ', $file->name);
        $this->assertEquals('(Бок ІІ, запіс мона, на хуткасці 9,5 см/с. Запіс на баку І адсутнічае).
Агульная працягласць 22:34
Інфармацыя на ўпакоўцы: Перапісана 13.05.97. Сильно фонит', $file->notes);
        $this->assertCount(4, $file->markers);

        $this->assertNull($file->markers[0]->timeFrom);
        $this->assertNull($file->markers[0]->timeTo);
        $this->assertNull($file->markers[0]->name);
        $this->assertEquals(CategoryType::ABOUT_RECORD, $file->markers[0]->category);
        $this->assertEquals('', $file->markers[0]->notes);
        $this->assertEquals('в. Верацеі, Старынкаўскі с/с, Глыбоцкі р-н', $file->markers[0]->place);
        $this->assertEquals(
            'Вершылоўскі Канстанцін Ульянавіч (Юльянавіч), 1892 г.н. (скрыпач)',
            $file->markers[0]->informantsText
        );
        $this->assertEquals('1971-01-01', $file->markers[0]->dateAction->format('Y-m-d'));
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_RECORD, $file->markers[0]->others);
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_BIRTH_LOCATION, $file->markers[0]->others);
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_MENTION, $file->markers[0]->others);

        $this->assertEquals('Вальс', $file->markers[1]->name);
        $this->assertEquals(CategoryType::MELODY, $file->markers[1]->category);
        $this->assertEquals('найгрыш на скрыпцы', $file->markers[1]->notes);
        $this->assertEquals('', $file->markers[1]->place);
        $this->assertEquals('', $file->markers[1]->informantsText);

        $this->assertEquals('Каробачка', $file->markers[2]->name);
        $this->assertEquals(CategoryType::MELODY, $file->markers[2]->category);
        $this->assertEquals('найгрыш на скрыпцы', $file->markers[2]->notes);
        $this->assertEquals('', $file->markers[2]->place);
        $this->assertEquals('', $file->markers[2]->informantsText);

        $this->assertEquals('Полька', $file->markers[3]->name);
        $this->assertEquals(CategoryType::MELODY, $file->markers[3]->category);
        $this->assertEquals('найгрыш на скрыпцы', $file->markers[3]->notes);
        $this->assertEquals('', $file->markers[3]->place);
        $this->assertEquals('', $file->markers[3]->informantsText);

        $subject = $subjects[1];
        $this->assertEquals('Стужка 1971-02', $subject->name);
        $this->assertCount(1, $subject->files);
        $file = $subject->files[0];
        $this->assertEquals('Бок І', $file->name);
        $this->assertEquals('(Бок І, запіс мона, на хуткасці 9,5 см/с. Запіс на баку ІІ адсутнічае).
Агульная працягласць 32:48
Інфармацыя на ўпакоўцы: дата 20.08.80 (гэта перазапіс ?)', $file->notes);
        $this->assertCount(5, $file->markers);

        $this->assertNull($file->markers[0]->name);
        $this->assertEquals(CategoryType::ABOUT_RECORD, $file->markers[0]->category);
        $this->assertEquals('', $file->markers[0]->notes);
        $this->assertEquals('в. Руднікі`, Глыбоцкі раён', $file->markers[0]->place);
        $this->assertEquals('', $file->markers[0]->informantsText);
        $this->assertEquals('1971-01-01', $file->markers[0]->dateAction->format('Y-m-d'));

        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_RECORD, $file->markers[0]->others);
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_BIRTH_LOCATION, $file->markers[0]->others);
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_MENTION, $file->markers[0]->others);

        $this->assertEquals('Строй цымбалаў', $file->markers[1]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[1]->category);
        $this->assertEquals('(дэманструе Бяляўскі Якаў Ігнацьевіч, 1909 г.н.)', $file->markers[1]->notes);
        $this->assertEquals('', $file->markers[1]->place);
        $this->assertEquals(
            'Бяляўскі Якаў Ігнацьевіч, 1909 г.н.',
            $file->markers[1]->informantsText
        );

        $this->assertEquals('Полька', $file->markers[2]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[2]->category);
        $this->assertEquals(
            'скрыпка (Бяляўскі), цымбалы (Забэла Аркадзій Кліменцьевіч)',
            $file->markers[2]->notes
        );
        $this->assertEquals('', $file->markers[2]->place);
        $this->assertEquals(
            'Забэла Аркадзій Кліменцьевіч',
            $file->markers[2]->informantsText
        );
    }
}
