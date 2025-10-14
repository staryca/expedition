<?php

declare(strict_types=1);

namespace App\Tests\Parser\BsuParser\BsuReportOrg;

use App\Dto\InformantDto;
use App\Dto\StudentDto;
use App\Parser\BsuParser;
use App\Repository\GeoPointRepository;
use App\Service\LocationService;
use App\Service\PersonService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BsuParserTest extends TestCase
{
    private readonly BsuParser $bsuParser;

    public function setUp(): void
    {
        parent::setUp();

        $geoPointRepository = $this->createMock(GeoPointRepository::class);
        $locationService = new LocationService($geoPointRepository);
        $personService = new PersonService();
        $this->bsuParser = new BsuParser($locationService, $personService);
    }

    public function testParse(): void
    {
        $id = 143922;
        $filename = __DIR__ . '/' . $id . '.html';
        $content = file_get_contents($filename);

        $dto = $this->bsuParser->parseContent($content);

        $this->assertEquals($id, $dto->id);
        $this->assertEquals(0, $dto->total); // Not a page of list
        $this->assertCount(0, $dto->links);
        $this->assertCount(0, $dto->children);
        $this->assertCount(2, $dto->authors);
        $this->assertCount(1, $dto->files);
        $this->assertEquals(134300, $dto->locationId);
        $this->assertEquals('Веткаўскі раён', $dto->locationText);
        $this->assertCount(12, $dto->dc);
        $this->assertCount(10, $dto->values);

        $reportData = $this->bsuParser->createReportData($dto);
        $this->assertEquals('в. Стаўбун Веткаўскі раён', $reportData->place);
        $this->assertNull($reportData->geoPoint);
        $this->assertCount(1, $reportData->blocks);
        $this->assertEquals('Зіма', $reportData->blocks[$id]->description);
        $this->assertEquals(
            'https://elib.bsu.by/handle/123456789/' . $id,
            $reportData->blocks[$id]->additional['source_url']
        );
        $this->assertCount(1, $reportData->blocks[$id]->files);
        $this->assertCount(2, $reportData->blocks[$id]->tags);
        $this->assertEquals('Каляндарна-абрадавыя песні', $reportData->blocks[$id]->tags[0]);
        $this->assertEquals('калядныя песні', $reportData->blocks[$id]->tags[1]);

        $personsBsu = $this->bsuParser->getBsuPersonsFromAuthors($dto->authors, $reportData, (string) $id);
        $this->assertCount(2, $personsBsu); // Organization and informant
        foreach ($personsBsu as $personBsu) {
            $this->assertNull($personBsu->birth);
            $this->assertFalse($personBsu->isStudent);
            $this->assertEquals($id, $personBsu->codeReport);
        }

        $organizations = [];
        $this->bsuParser->getOrganizations($personsBsu, $organizations);
        $this->assertCount(1, $organizations);
        $this->assertEquals('фалькорна-этнаграфічны ансамбль', $organizations[0]->name);

        $student = new StudentDto();
        $student->name = 'Раговіч Уладзімір Пятровіч';
        $student->addLocation('в. Стаўбун Веткаўскі раён');
        $students = [$student];
        $this->bsuParser->getStudents($personsBsu, $students);
        $this->assertCount(1, $students);

        /** @var array<InformantDto> $informants */
        $informants = [];
        $this->bsuParser->getInformants($personsBsu, $informants);
        $this->assertCount(1, $informants);
        $this->assertEquals('Раговіч Уладзімір', $informants[0]->name);

        // Compare students and informants
        $this->bsuParser->compareInformantsAndStudents($informants, $students);
        $this->assertCount(0, $informants); // Informant moved to student

        $reportBlocksData = $this->bsuParser->getReportBlocks($organizations, $informants);
        $this->assertCount(1, $reportBlocksData);
        $this->assertArrayHasKey($id, $reportBlocksData);

        $this->bsuParser->mergeReportBlocks([$reportData], $reportBlocksData);
        $this->assertCount(1, $reportBlocksData);
        $this->assertArrayHasKey($id, $reportBlocksData);
        $this->assertEquals(0, $reportData->blocks[$id]->organizationKey); // key to the organization
        $this->assertCount(0, $reportData->blocks[$id]->informantKeys);
    }
}
