<?php

declare(strict_types=1);

namespace App\Tests\Parser\BsuParser\BsuReport;

use App\Dto\InformantDto;
use App\Entity\Expedition;
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
        $id = 135263;
        $filename = __DIR__ . '/' . $id . '.html';
        $content = file_get_contents($filename);

        $dto = $this->bsuParser->parseContent($content);

        $this->assertEquals($id, $dto->id);
        $this->assertEquals(0, $dto->total); // Not a page of list
        $this->assertCount(0, $dto->links);
        $this->assertCount(0, $dto->children);
        $this->assertCount(2, $dto->authors);
        $this->assertCount(1, $dto->files);
        $this->assertEquals(122683, $dto->locationId);
        $this->assertEquals('Ашмянскі раён', $dto->locationText);
        $this->assertCount(12, $dto->dc);
        $this->assertCount(10, $dto->values);

        $expedition = new Expedition();
        $report = $this->bsuParser->createReport($dto, $expedition);

        $this->assertEquals($id, $report->getTempValue('id'));
        $this->assertEquals('в. Гальшаны Ашмянскі раён', $report->getGeoNotes());
        $this->assertNull($report->getGeoPoint());

        $personsBsu = $this->bsuParser->getBsuPersons([$report]);
        $this->assertCount(2, $personsBsu);
        foreach ($personsBsu as $personBsu) {
            $this->assertNull($personBsu->birth);
            $this->assertFalse($personBsu->isStudent);
            $this->assertEquals($id, $personBsu->codeReport);
        }

        $organizations = [];
        $this->bsuParser->getOrganizations($personsBsu, $organizations);
        $this->assertCount(0, $organizations);

        $students = [];
        $this->bsuParser->getStudents($personsBsu, $students);
        $this->assertCount(0, $students);

        /** @var array<InformantDto> $informants */
        $informants = [];
        $this->bsuParser->getInformants($personsBsu, $informants);
        $this->assertCount(2, $informants);
        $this->assertEquals('Сяргееў Андрэй Уладзіміравіч', $informants[0]->name);
        $this->assertEquals('Помалева М.А.', $informants[1]->name);

        $reportBlocksData = $this->bsuParser->getReportBlocks($organizations, $informants);
        $this->assertCount(1, $reportBlocksData);
        $this->assertArrayHasKey($id, $reportBlocksData);
        $this->assertNull($reportBlocksData[$id]->organizationKey);
        $this->assertCount(2, $reportBlocksData[$id]->informantKeys);
    }
}
