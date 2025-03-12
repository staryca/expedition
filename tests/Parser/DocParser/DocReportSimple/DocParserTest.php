<?php

declare(strict_types=1);

namespace App\Tests\Parser\DocParser\DocReportSimple;

use App\Entity\GeoPoint;
use App\Entity\Type\ReportBlockType;
use App\Helper\TextHelper;
use App\Parser\DocParser;
use App\Repository\GeoPointRepository;
use App\Repository\UserRepository;
use App\Service\LocationService;
use App\Service\PersonService;
use App\Service\ReportService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class DocParserTest extends TestCase
{
    private readonly DocParser $docParser;
    private readonly GeoPointRepository $geoPointRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->geoPointRepository = $this->createMock(GeoPointRepository::class);

        $textHelper = new TextHelper();
        $locationService = new LocationService($this->geoPointRepository, $textHelper);
        $personService = new PersonService($textHelper);
        $userService = new UserService(
            $this->createMock(UserRepository::class), $textHelper
        );
        $reportService = new ReportService();
        $this->docParser = new DocParser($locationService, $personService, $userService, $reportService);
    }

    public function testParse(): void
    {
        $geoPoint = new GeoPoint('243026442');
        $this->geoPointRepository->expects($this->once())
            ->method('findByNameAndDistrict')
            ->willReturn([$geoPoint]);

        $filename = __DIR__ . '/report.xml';
        $content = file_get_contents($filename);

        $reportsData = $this->docParser->parseDoc($content);

        $this->assertCount(1, $reportsData);
        $report = $reportsData[0];

        $this->assertEquals('2022-08-19', $report->dateAction->format('Y-m-d'));
        $this->assertEquals(243026442, $report->geoPoint->getId());
        $this->assertCount(3, $report->userRoles);
        $this->assertCount(0, $report->tips);

        $this->assertCount(1, $report->blocks);
        $this->assertEquals(ReportBlockType::TYPE_CEMETERY_TOUR, $report->blocks[0]->type);
        $this->assertCount(2, $report->blocks[0]->additional);
        $this->assertArrayHasKey('Photo', $report->blocks[0]->additional);
        $this->assertArrayHasKey('code', $report->blocks[0]->additional);
        $this->assertCount(7, $report->blocks[0]->episodes);
    }
}
