<?php

declare(strict_types=1);

namespace App\Tests\Handler\VopisDetailedHandler\SimpleTest;

use App\Dto\FileMarkerDto;
use App\Dto\InformantDto;
use App\Dto\OrganizationDto;
use App\Handler\VopisDetailedHandler;
use App\Manager\ReportManager;
use App\Parser\VopisDetailedParser;
use App\Repository\DanceRepository;
use App\Repository\ExpeditionRepository;
use App\Repository\GeoPointRepository;
use App\Repository\UserRepository;
use App\Service\CategoryService;
use App\Service\LocationService;
use App\Service\PersonService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VopisDetailedHandlerTest extends TestCase
{
    private readonly VopisDetailedHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $geoPointRepository = $this->createMock(GeoPointRepository::class);

        $locationService = new LocationService($geoPointRepository);
        /** @var ExpeditionRepository|MockObject $expeditionRepository */
        $expeditionRepository = $this->createMock(ExpeditionRepository::class);
        $personService = new PersonService();
        /** @var UserRepository|MockObject $userRepository */
        $userRepository = $this->createMock(UserRepository::class);
        /** @var ReportManager|MockObject $reportManager */
        $reportManager = $this->createMock(ReportManager::class);

        $danceRepository = $this->createMock(DanceRepository::class);
        $categoryService = new CategoryService($danceRepository);
        $parser = new VopisDetailedParser($locationService, $categoryService);

        $this->handler = new VopisDetailedHandler(
            $parser,
            $expeditionRepository,
            $personService,
            $userRepository,
            $reportManager
        );
    }

    public function testBase(): void
    {
        $filename = __DIR__ . '/vopis.csv';
        $subjects = $this->handler->checkFile($filename);

        $this->assertCount(2, $subjects);
        $subject = $subjects[0];
        $this->assertEquals('600_Kozenka', $subject->name);
        $this->assertCount(2, $subject->files);
        $this->assertEquals('бок А', $subject->files[0]->name);
        $this->assertCount(3, $subject->files[0]->markers);
        $this->assertEquals('бок В', $subject->files[1]->name);
        $this->assertCount(4, $subject->files[1]->markers);

        $subject = $subjects[1];
        $this->assertEquals('600a_Kozenka', $subject->name);
        $this->assertCount(1, $subject->files);
        $this->assertEquals('бок А', $subject->files[0]->name);
        $this->assertCount(3, $subject->files[0]->markers);

        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_MENTION, $subject->files[0]->markers[0]->others);
        $this->assertArrayHasKey(FileMarkerDto::OTHER_MENTION, $subject->files[0]->markers[1]->others);
        $this->assertEquals('в. Міцькаўшчына, Аршанскі', $subject->files[0]->markers[1]->others[FileMarkerDto::OTHER_MENTION]);
        $this->assertArrayNotHasKey(FileMarkerDto::OTHER_MENTION, $subject->files[0]->markers[2]->others);

        /* Detect organizations and informants */
        /** @var array<OrganizationDto> $organizations */
        $organizations = [];
        /** @var array<InformantDto> $informants */
        $informants = [];
        $this->handler->detectOrganizationsAndInformants($subjects, $organizations, $informants);

        $this->assertCount(2, $organizations);
        $organization = $organizations[3];
        $this->assertEquals('гурт', $organization->name);
        $this->assertEquals('в. Пожарцы, Пастаўскі раён', $organization->place);
        $this->assertCount(0, $organization->informants);
        $this->assertCount(0, $organization->informantKeys);
        $organization = $organizations[4];
        $this->assertEquals('гурт', $organization->name);
        $this->assertEquals('в. Велеўшчына, Лепельскі раён', $organization->place);
        $this->assertCount(0, $organization->informants);
        $this->assertCount(0, $organization->informantKeys);

        $this->assertCount(3, $informants);
        $informant = $informants[0];
        $this->assertEquals('Рагіня Пётр Пятровіч', $informant->name);
        $this->assertEquals(1922, $informant->birth);
        $this->assertEquals('в. Груздава, Пастаўскі раён', $informant->place);
        $this->assertEquals('в. Абольцы, Талачынскі раён', $informant->birthPlace->place);
        $informant = $informants[1];
        $this->assertEquals('Крывенька Уладзімір Ануфрыевіч', $informant->name);
        $this->assertEquals(1930, $informant->birth);
        $this->assertEquals('в. Пожарцы, Пастаўскі раён', $informant->place);
        $informant = $informants[2];
        $this->assertEquals('Мелец Станіслаў Пятровіч', $informant->name);
        $this->assertEquals(1926, $informant->birth);
        $this->assertEquals('в. Пожарцы, Пастаўскі раён', $informant->place);

        /* Create reports */
        $reportsData = $this->handler->createReportsData($subjects);

        $this->assertCount(4, $reportsData);

        $report = $reportsData[0];
        $this->assertEquals('в. Груздава, Пастаўскі раён', $report->place);
        $this->assertEquals('23/10/1987', $report->dateAction->format('d/m/Y'));
        $this->assertCount(1, $report->blocks);

        $report = $reportsData[1];
        $this->assertEquals('в. Груздава, Пастаўскі раён', $report->place);
        $this->assertEquals('26/10/1987', $report->dateAction->format('d/m/Y'));
        $this->assertCount(1, $report->blocks);

        $report = $reportsData[2];
        $this->assertEquals('в. Пожарцы, Пастаўскі раён', $report->place);
        $this->assertEquals('27/10/1987', $report->dateAction->format('d/m/Y'));
        $this->assertCount(2, $report->blocks);
        $this->assertCount(2, $report->blocks[0]->informantKeys);
        $this->assertContains(1, $report->blocks[0]->informantKeys);
        $this->assertContains(2, $report->blocks[0]->informantKeys);

        $report = $reportsData[3];
        $this->assertEquals('в. Велеўшчына, Лепельскі раён', $report->place);
        $this->assertEquals('28/10/1987', $report->dateAction->format('d/m/Y'));
        $this->assertCount(1, $report->blocks);
        $this->assertCount(0, $report->blocks[0]->informantKeys);
    }
}
