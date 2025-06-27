<?php

declare(strict_types=1);

namespace App\Tests\Parser\BsuParser\BsuReportBirth;

use App\Dto\InformantDto;
use App\Dto\StudentDto;
use App\Helper\TextHelper;
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
        $textHelper = new TextHelper();
        $locationService = new LocationService($geoPointRepository, $textHelper);
        $personService = new PersonService($textHelper);
        $this->bsuParser = new BsuParser($locationService, $personService);
    }

    public function testParse(): void
    {
        $id = 139510;
        $filename = __DIR__ . '/' . $id . '.html';
        $content = file_get_contents($filename);

        $dto = $this->bsuParser->parseContent($content);

        $this->assertEquals($id, $dto->id);
        $this->assertEquals(0, $dto->total); // Not a page of list
        $this->assertCount(0, $dto->links);
        $this->assertCount(0, $dto->children);
        $this->assertCount(5, $dto->authors);
        $this->assertCount(1, $dto->files);
        $this->assertEquals(134300, $dto->locationId);
        $this->assertEquals('Веткаўскі раён', $dto->locationText);
        $this->assertCount(12, $dto->dc);
        $this->assertCount(10, $dto->values);

        $reportData = $this->bsuParser->createReportData($dto);
        $this->assertEquals('Столбун Веткаўскі раён', $reportData->place);
        $this->assertNull($reportData->geoPoint);
        $this->assertCount(1, $reportData->blocks);
        $this->assertEquals($id, (int) $reportData->blocks[$id]->code);
        $this->assertEquals('А на гарэ лён', $reportData->blocks[$id]->description);
        $this->assertEquals(
            'https://elib.bsu.by/handle/123456789/' . $id,
            $reportData->blocks[$id]->additional['source_url']
        );
        $this->assertCount(1, $reportData->blocks[$id]->files);
        $this->assertCount(2, $reportData->blocks[$id]->tags);
        $this->assertEquals('Пазаабрадавая паэзія', $reportData->blocks[$id]->tags[0]);
        $this->assertEquals('сямейна-бытавыя песні', $reportData->blocks[$id]->tags[1]);

        $personsBsu = $this->bsuParser->getBsuPersonsFromAuthors($dto->authors, $reportData, (string) $id);
        $this->assertCount(5, $personsBsu);
        foreach ($personsBsu as $personBsu) {
            $this->assertFalse($personBsu->isStudent);
            $this->assertEquals($id, $personBsu->codeReport);
        }
        $this->assertEquals('Зуева М.Л.', $personsBsu[0]->name);
        $this->assertEquals(1927, $personsBsu[0]->birth);
        $this->assertEquals('Феськова А.Л.', $personsBsu[1]->name);
        $this->assertEquals(1922, $personsBsu[1]->birth);
        $this->assertEquals('Таранова М.Д.', $personsBsu[2]->name);
        $this->assertEquals(1924, $personsBsu[2]->birth);
        $this->assertEquals('Чулешова М.П.', $personsBsu[3]->name);
        $this->assertEquals(1930, $personsBsu[3]->birth);

        $organizations = [];
        $this->bsuParser->getOrganizations($personsBsu, $organizations);
        $this->assertCount(0, $organizations);

        $student = new StudentDto();
        $student->name = 'Рогович В.';
        $student->addLocation('Столбун Веткаўскі раён');
        $students = [$student];
        $this->bsuParser->getStudents($personsBsu, $students);
        $this->assertCount(1, $students);

        /** @var array<InformantDto> $informants */
        $informants = [];
        $this->bsuParser->getInformants($personsBsu, $informants);
        $this->assertCount(5, $informants);
        $this->assertEquals('Рогович В.И.', $informants[4]->name);

        // Compare students and informants
        $this->bsuParser->compareInformantsAndStudents($informants, $students);
        $this->assertCount(4, $informants); // Informant moved to student

        $reportBlocksData = $this->bsuParser->getReportBlocks($organizations, $informants);
        $this->assertCount(1, $reportBlocksData);
        $this->assertArrayHasKey($id, $reportBlocksData);

        $this->bsuParser->mergeReportBlocks([$reportData], $reportBlocksData);
        $this->assertCount(1, $reportBlocksData);
        $this->assertArrayHasKey($id, $reportBlocksData);
        $this->assertNull($reportData->blocks[$id]->organizationKey);
        $this->assertCount(4, $reportData->blocks[$id]->informantKeys);
    }
}
