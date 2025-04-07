<?php

declare(strict_types=1);

namespace App\Tests\Parser\VopisDetailedParser\VopisSubjectsSimple;

use App\Dto\FileMarkerDto;
use App\Entity\Type\CategoryType;
use App\Helper\TextHelper;
use App\Parser\VopisDetailedParser;
use App\Repository\GeoPointRepository;
use App\Service\LocationService;
use PHPUnit\Framework\TestCase;

class VopisParserTest extends TestCase
{
    private readonly VopisDetailedParser $parser;
    private readonly GeoPointRepository $geoPointRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->geoPointRepository = $this->createMock(GeoPointRepository::class);

        $textHelper = new TextHelper();
        $locationService = new LocationService($this->geoPointRepository, $textHelper);
        $this->parser = new VopisDetailedParser($locationService);
    }

    public function testParse(): void
    {
        $filename = __DIR__ . '/vopis.csv';
        $content = file_get_contents($filename);

        $subjects = $this->parser->parse($content);

        $this->assertCount(2, $subjects);

        $subject = $subjects[0];
        $this->assertEquals('600_Kozenka', $subject->name);
        $this->assertCount(2, $subject->files);
        $file = $subject->files[0];
        $this->assertEquals('бок А', $file->name);
        $this->assertCount(3, $file->markers);

        $this->assertEquals('00:04.109', $file->markers[0]->timeFrom);
        $this->assertEquals('00:26.853', $file->markers[0]->timeTo);
        $this->assertNull($file->markers[0]->name);
        $this->assertEquals(CategoryType::ABOUT_RECORD, $file->markers[0]->category);
        $this->assertEquals('', $file->markers[0]->notes);
        $this->assertEquals('в. Груздава, Пастаўскі раён', $file->markers[0]->place);
        $this->assertEquals('Пётр Пятровіч Рагіня, 1922 г.н.', $file->markers[0]->informantsText);

        $this->assertEquals('1987-10-23', $file->markers[0]->dateAction->format('Y-m-d'));
        $this->assertArrayHasKey(FileMarkerDto::OTHER_RECORD, $file->markers[0]->others);
        $this->assertEquals('г. Лепель', $file->markers[0]->others[FileMarkerDto::OTHER_RECORD]);
        $this->assertArrayHasKey(FileMarkerDto::OTHER_BIRTH_LOCATION, $file->markers[0]->others);
        $this->assertEquals('в. Абольцы, Талачынскі раён', $file->markers[0]->others[FileMarkerDto::OTHER_BIRTH_LOCATION]);
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_MENTION, $file->markers[0]->others);

        $this->assertEquals('00:26.853', $file->markers[1]->timeFrom);
        $this->assertEquals('01:33.500', $file->markers[1]->timeTo);
        $this->assertEquals('Субота', $file->markers[1]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[1]->category);
        $this->assertEquals('скрыпка', $file->markers[1]->notes);
        $this->assertEquals('в. Груздава, Пастаўскі раён', $file->markers[1]->place);
        $this->assertEquals('Пётр Пятровіч Рагіня, 1922 г.н.', $file->markers[1]->informantsText);

        $this->assertEquals('01:33.500', $file->markers[2]->timeFrom);
        $this->assertNull($file->markers[2]->timeTo);
        $this->assertEquals('[Непазнаны]', $file->markers[2]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[2]->category);
        $this->assertEquals('скрыпка', $file->markers[2]->notes);
        $this->assertEquals('в. Груздава, Пастаўскі раён', $file->markers[2]->place);
        $this->assertEquals('Пётр Пятровіч Рагіня, 1922 г.н.', $file->markers[2]->informantsText);

        $file = $subject->files[1];
        $this->assertEquals('бок В', $file->name);
        $this->assertCount(4, $file->markers);

        $this->assertEquals('00:04.000', $file->markers[0]->timeFrom);
        $this->assertEquals('00:19.078', $file->markers[0]->timeTo);
        $this->assertEquals('', $file->markers[0]->name);
        $this->assertEquals(CategoryType::ABOUT_INFORMANT, $file->markers[0]->category);
        $this->assertEquals('', $file->markers[0]->notes);
        $this->assertEquals('в. Груздава, Пастаўскі раён', $file->markers[0]->place);
        $this->assertEquals('Пётр Пятровіч Рагіня, 1922 г.н.', $file->markers[0]->informantsText);

        $this->assertEquals('1987-10-26', $file->markers[0]->dateAction->format('Y-m-d'));
        $this->assertArrayHasKey(FileMarkerDto::OTHER_RECORD, $file->markers[0]->others);
        $this->assertEquals('г. Лепель', $file->markers[0]->others[FileMarkerDto::OTHER_RECORD]);
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_BIRTH_LOCATION, $file->markers[0]->others);
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_MENTION, $file->markers[0]->others);

        $this->assertEquals('Полька', $file->markers[3]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[3]->category);
        $this->assertEquals('скрыпка, гармонік', $file->markers[3]->notes);
        $this->assertEquals(
            'Уладзімір Ануфрыевіч Крывенька, 1930 г.н. (гармонік), Станіслаў Пятровіч Мелец, 1926 г.н. (скрыпка)',
            $file->markers[3]->informantsText
        );

        $subject = $subjects[1];
        $this->assertEquals('600a_Kozenka', $subject->name);
        $this->assertCount(1, $subject->files);
        $file = $subject->files[0];
        $this->assertEquals('бок А', $file->name);
        $this->assertCount(3, $file->markers);

        $this->assertEquals('00:04.200', $file->markers[0]->timeFrom);
        $this->assertEquals('00:35.805', $file->markers[0]->timeTo);
        $this->assertNull($file->markers[0]->name);
        $this->assertEquals(CategoryType::ABOUT_RECORD, $file->markers[0]->category);
        $this->assertEquals('', $file->markers[0]->notes);
        $this->assertEquals('в. Пожарцы, Пастаўскі раён', $file->markers[0]->place);
        $this->assertEquals(
            'Уладзімір Ануфрыевіч Крывенька, 1930 г.н. (гармонік), Станіслаў Пятровіч Мелец, 1926 г.н. (скрыпка)',
            $file->markers[0]->informantsText
        );
        $this->assertNull($file->markers[0]->dateAction);

        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_RECORD, $file->markers[0]->others);
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_BIRTH_LOCATION, $file->markers[0]->others);
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_MENTION, $file->markers[0]->others);

        $this->assertEquals('00:35.805', $file->markers[1]->timeFrom);
        $this->assertEquals('03:22.600', $file->markers[1]->timeTo);
        $this->assertEquals('Вальс "Спатканне"', $file->markers[1]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[1]->category);
        $this->assertEquals('скрыпка, гармонік', $file->markers[1]->notes);
        $this->assertEquals('в. Пожарцы, Пастаўскі раён', $file->markers[1]->place);
        $this->assertEquals(
            'Станіслаў Пятровіч Мелец, 1926 г.н. (скрыпка)',
            $file->markers[1]->informantsText
        );

        $this->assertEquals('03:22.600', $file->markers[2]->timeFrom);
        $this->assertNull($file->markers[2]->timeTo);
        $this->assertEquals('Вальс "Ніначка"', $file->markers[2]->name);
        $this->assertEquals(CategoryType::STORY, $file->markers[2]->category);
        $this->assertEquals('скрыпка, гармонік', $file->markers[2]->notes);
        $this->assertEquals('в. Пожарцы, Пастаўскі раён', $file->markers[2]->place);
        $this->assertEquals(
            'Станіслаў Пятровіч Мелец, 1926 г.н. (скрыпка)',
            $file->markers[2]->informantsText
        );
        $this->assertArrayHasKey(FileMarkerDto::OTHER_MENTION, $file->markers[2]->others);
        $this->assertEquals('в. Ляды, Дубровенскі р-н', $file->markers[2]->others[FileMarkerDto::OTHER_MENTION]);
    }
}
