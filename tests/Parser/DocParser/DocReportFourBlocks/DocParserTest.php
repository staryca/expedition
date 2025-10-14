<?php

declare(strict_types=1);

namespace App\Tests\Parser\DocParser\DocReportFourBlocks;

use App\Entity\GeoPoint;
use App\Entity\Type\CategoryType;
use App\Entity\Type\ReportBlockType;
use App\Helper\TextHelper;
use App\Parser\DocParser;
use App\Repository\GeoPointRepository;
use App\Repository\UserRepository;
use App\Service\LocationService;
use App\Service\PersonService;
use App\Service\ReportService;
use App\Service\UserService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;
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
        $locationService = new LocationService($this->geoPointRepository);
        $personService = new PersonService();
        $userService = new UserService(
            $this->createMock(UserRepository::class),
            $textHelper,
            $this->createMock(EntityManager::class),
        );
        $reportService = new ReportService();
        $this->docParser = new DocParser($locationService, $personService, $userService, $reportService);
    }

    public function testParse(): void
    {
        $geoPoint = new GeoPoint('243026442');
        $this->geoPointRepository->expects($this->atMost(4))
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
        $this->assertCount(1, $report->tips);
        $this->assertEquals(
            'Дачка скрыпака Барадулькіна Арцёма Іванавіча Ліда жыве ў в. Марачкова (ня ўпэўнена) - ёй 91 год.',
            $report->tips[0]
        );
        $this->assertCount(1, $report->tasks);
        $this->assertEquals(
            'Даслаць фотаздымкі Пацук Зінаідзе Сьцяпанаўне -в. Якубава, вул. Леменская, д.40',
            $report->tasks[0]
        );

        $this->assertCount(4, $report->blocks);

        $this->assertEquals(ReportBlockType::TYPE_CONVERSATION, $report->blocks[0]->type);
        $this->assertCount(3, $report->blocks[0]->additional);
        $this->assertArrayHasKey('Audio', $report->blocks[0]->additional);
        $this->assertArrayHasKey('Photo', $report->blocks[0]->additional);
        $this->assertArrayHasKey('code', $report->blocks[0]->additional);
        $this->assertCount(1, $report->blocks[0]->informants);
        $this->assertEquals('Марозаў Анатолій Уладзіміравіч', $report->blocks[0]->informants[0]->name);
        $this->assertEquals(243026442, $report->blocks[0]->informants[0]->geoPoint->getId());
        $this->assertCount(10, $report->blocks[0]->getEpisodes());
        $this->assertEquals(CategoryType::SONGS, $report->blocks[0]->getEpisodes()[1]->getCategory());

        $this->assertEquals(ReportBlockType::TYPE_CONVERSATION, $report->blocks[1]->type);
        $this->assertCount(4, $report->blocks[1]->additional);
        $this->assertArrayHasKey('Audio', $report->blocks[1]->additional);
        $this->assertArrayHasKey('Photo', $report->blocks[1]->additional);
        $this->assertArrayHasKey('Video', $report->blocks[1]->additional);
        $this->assertArrayHasKey('code', $report->blocks[1]->additional);
        $this->assertCount(1, $report->blocks[0]->informants);
        $this->assertEquals('Пацук (Барадулькіна) Зінаіда Сьцяпанаўна', $report->blocks[1]->informants[0]->name);
        $this->assertEquals(243026442, $report->blocks[1]->informants[0]->geoPoint->getId());
        $this->assertCount(28, $report->blocks[1]->getEpisodes());
        $this->assertEquals(CategoryType::STORY, $report->blocks[1]->getEpisodes()[0]->getCategory());

        $this->assertEquals(ReportBlockType::TYPE_CONVERSATION, $report->blocks[2]->type);
        $this->assertCount(4, $report->blocks[2]->additional);
        $this->assertArrayHasKey('Audio', $report->blocks[2]->additional);
        $this->assertArrayHasKey('Photo', $report->blocks[2]->additional);
        $this->assertArrayHasKey('Video', $report->blocks[2]->additional);
        $this->assertArrayHasKey('code', $report->blocks[2]->additional);
        $this->assertCount(1, $report->blocks[0]->informants);
        $this->assertEquals('Барадулькіна (Жукава) Марыя Сьцяпанаўна', $report->blocks[2]->informants[0]->name);
        $this->assertEquals(243026442, $report->blocks[2]->informants[0]->geoPoint->getId());
        $this->assertCount(24, $report->blocks[2]->getEpisodes());

        $this->assertEquals(ReportBlockType::TYPE_VILLAGE_TOUR, $report->blocks[3]->type);
        $this->assertCount(2, $report->blocks[3]->additional);
        $this->assertArrayHasKey('Photo', $report->blocks[3]->additional);
        $this->assertArrayHasKey('code', $report->blocks[3]->additional);
        $this->assertCount(0, $report->blocks[3]->informants);
        $this->assertCount(1, $report->blocks[3]->getEpisodes());
    }
}
