<?php

declare(strict_types=1);

namespace App\Tests\Handler\VopisDetailedHandler\WithoutTimeTest;

use App\Dto\FileMarkerDto;
use App\Dto\InformantDto;
use App\Dto\OrganizationDto;
use App\Handler\VopisDetailedHandler;
use App\Helper\TextHelper;
use App\Manager\ReportManager;
use App\Parser\VopisDetailedParser;
use App\Repository\ExpeditionRepository;
use App\Repository\GeoPointRepository;
use App\Repository\UserRepository;
use App\Service\LocationService;
use App\Service\PersonService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VopisDetailedHandlerTest extends TestCase
{
    private readonly VopisDetailedParser $parser;
    private readonly VopisDetailedHandler $handler;
    private readonly GeoPointRepository $geoPointRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->geoPointRepository = $this->createMock(GeoPointRepository::class);

        $textHelper = new TextHelper();
        $locationService = new LocationService($this->geoPointRepository, $textHelper);
        /** @var ExpeditionRepository|MockObject $expeditionRepository */
        $expeditionRepository = $this->createMock(ExpeditionRepository::class);
        $personService = new PersonService($textHelper);
        /** @var UserRepository|MockObject $userRepository */
        $userRepository = $this->createMock(UserRepository::class);
        /** @var ReportManager|MockObject $reportManager */
        $reportManager = $this->createMock(ReportManager::class);
        $this->parser = new VopisDetailedParser($locationService);

        $this->handler = new VopisDetailedHandler(
            $this->parser,
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
        $this->assertEquals('Стужка 1971-01', $subject->name);
        $this->assertCount(1, $subject->files);
        $this->assertEquals('Бок ІІ', $subject->files[0]->name);
        $this->assertCount(8, $subject->files[0]->markers);

        $subject = $subjects[1];
        $this->assertEquals('Стужка 1971-02', $subject->name);
        $this->assertCount(1, $subject->files);
        $this->assertEquals('Бок І', $subject->files[0]->name);
        $this->assertCount(5, $subject->files[0]->markers);

        /* Detect organizations and informants */
        /** @var array<OrganizationDto> $organizations */
        $organizations = [];
        /** @var array<InformantDto> $informants */
        $informants = [];
        $this->handler->detectOrganizationsAndInformants($subjects, $organizations, $informants);

        $this->assertCount(1, $organizations);
        $organization = $organizations[1];
        $this->assertEquals('сямейнае трыё цымбалістаў', $organization->name);
        $this->assertEquals('в. Пожарцы, Пастаўскі р-н', $organization->place);
        $this->assertCount(2, $organization->informants);
        $this->assertCount(2, $organization->informantKeys);

        $this->assertCount(7, $informants);
        $informant = $informants[0];
        $this->assertEquals('Мацкевіч Вольга Ўладзіміраўна', $informant->name);
        $this->assertEquals(1902, $informant->birth);
        $this->assertEquals('в. Празарокі, Глыбоцкі раён', $informant->place);
        $this->assertNull($informant->birthPlace);
        $informant = $informants[1];
        $this->assertEquals('Мацкевіч Іван Іосіфавіч', $informant->name);
        $this->assertEquals(1922, $informant->birth);
        $this->assertEquals('в. Пожарцы, Пастаўскі р-н', $informant->place);
        $informant = $informants[2];
        $this->assertEquals('Марыя Ігнацьеўна (жонка)', $informant->name);
        $this->assertEquals(1922, $informant->birth);
        $this->assertEquals('в. Пожарцы, Пастаўскі р-н', $informant->place);
        // ... (only 3 from 7)

        /* Create reports */
        $reportsData = $this->handler->createReportsData($subjects);

        $this->assertCount(3, $reportsData);

        $report = $reportsData[0];
        $this->assertEquals('в. Празарокі, Глыбоцкі раён', $report->place);
        $this->assertEquals('01/01/1971', $report->dateAction->format('d/m/Y'));
        $this->assertCount(1, $report->blocks);

        $report = $reportsData[1];
        $this->assertEquals('в. Пожарцы, Пастаўскі р-н', $report->place);
        $this->assertEquals('01/01/1971', $report->dateAction->format('d/m/Y'));
        $this->assertCount(2, $report->blocks);

        $report = $reportsData[2];
        $this->assertEquals('в. Руднікі`, Глыбоцкі раён', $report->place);
        $this->assertEquals('01/01/1971', $report->dateAction->format('d/m/Y'));
        $this->assertCount(1, $report->blocks);
        $this->assertCount(4, $report->blocks[0]->informantKeys);
        $this->assertContains(3, $report->blocks[0]->informantKeys);
        $this->assertContains(4, $report->blocks[0]->informantKeys);
        $this->assertContains(5, $report->blocks[0]->informantKeys);
        $this->assertContains(6, $report->blocks[0]->informantKeys);
    }
}
