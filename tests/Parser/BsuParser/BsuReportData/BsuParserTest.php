<?php

declare(strict_types=1);

namespace App\Tests\Parser\BsuParser\BsuReportData;

use App\Dto\InformantDto;
use App\Entity\GeoPoint;
use App\Helper\TextHelper;
use App\Parser\BsuParser;
use App\Repository\GeoPointRepository;
use App\Service\LocationService;
use App\Service\PersonService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BsuParserTest extends TestCase
{
    private readonly GeoPointRepository|MockObject $geoPointRepository;
    private readonly BsuParser $bsuParser;

    public function setUp(): void
    {
        parent::setUp();

        $this->geoPointRepository = $this->createMock(GeoPointRepository::class);

        $textHelper = new TextHelper();
        $locationService = new LocationService($this->geoPointRepository, $textHelper);
        $personService = new PersonService($textHelper);
        $this->bsuParser = new BsuParser($locationService, $personService, $textHelper);
    }

    public function testParse(): void
    {
        $geoPoint = new GeoPoint('123456789');
        $this->geoPointRepository->expects($this->once())
            ->method('findByNameAndDistrict')
            ->willReturn([$geoPoint]);

        $id = 143698;
        $filename = __DIR__ . '/' . $id . '.html';
        $content = file_get_contents($filename);

        $dto = $this->bsuParser->parseContent($content);

        $this->assertEquals($id, $dto->id);
        $this->assertEquals(0, $dto->total); // Not a page of list
        $this->assertCount(0, $dto->links);
        $this->assertCount(0, $dto->children);
        $this->assertCount(2, $dto->authors);
        $this->assertCount(1, $dto->files);
        $this->assertEquals(125261, $dto->locationId);
        $this->assertEquals('Гродзенскі раён', $dto->locationText);
        $this->assertCount(12, $dto->dc);
        $this->assertCount(10, $dto->values);

        $reportData = $this->bsuParser->createReportData($dto);
        $this->assertEquals($id, (int) $reportData->code);
        $this->assertEquals('в. Палаткова Гродзенскі раён', $reportData->geoNotes);
        $this->assertNotNull($reportData->geoPoint);

        $personsBsu = $this->bsuParser->getBsuPersonsFromAuthors($dto->authors, $reportData);
        $this->assertCount(1, $personsBsu); // "невядомы" прапушчан ўжо
        $this->assertNull($personsBsu[0]->birth); // 62 гады, але год запісу невядомы
        $this->assertFalse($personsBsu[0]->isStudent);
        $this->assertEquals($id, $personsBsu[0]->codeReport);

        $organizations = [];
        $this->bsuParser->getOrganizations($personsBsu, $organizations);
        $this->assertCount(0, $organizations);

        $students = [];
        $this->bsuParser->getStudents($personsBsu, $students);
        $this->assertCount(0, $students);

        /** @var array<InformantDto> $informants */
        $informants = [];
        $this->bsuParser->getInformants($personsBsu, $informants);
        $this->assertCount(1, $informants);
        $this->assertEquals('Говор Наталья Іосіфаўна', $informants[0]->name);

        $reportBlocksData = $this->bsuParser->getReportBlocks($organizations, $informants);
        $this->assertCount(1, $reportBlocksData);
        $this->assertNull($reportBlocksData[$id]->organizationKey);
        $this->assertCount(1, $reportBlocksData[$id]->informantKeys);
    }
}
