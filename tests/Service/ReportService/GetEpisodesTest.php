<?php

declare(strict_types=1);

namespace App\Tests\Service\ReportService;

use App\Entity\Type\CategoryType;
use App\Service\ReportService;
use PHPUnit\Framework\TestCase;

class GetEpisodesTest extends TestCase
{
    private readonly ReportService $reportService;

    public function setUp(): void
    {
        parent::setUp();

        $this->reportService = new ReportService();
    }

    public function testGetEpisodesWithOtherCategories(): void
    {
        $contents = [
            'Аповед пра мясцовых гарманістаў і пра тое, што танчылі і без музыкаў.',
            'Танцы: стараданіе, кракавяк, сямёнаўна, полька, дасада. Прыпеўкі пад страданіе і сямёнаўну.',
            'Аповед пра тое, як дзеці вучыліся танцаваць. Гарманісту плацілі яйкамі.',
            'Вяселле: як закідывалі зайца, як абсевалі, як кусалі хлеб, нявесту садзяць на шубу "каб была багата, гарбата", пра падарункі (насавікі), пра тое, як бутылку гарелкі перавязвалі краснай лентай і везлі цёшчы за чэсную нявесту,',
            'Аповед пра тое, як рабілі квас, што кублы выкарыстоўвалі замест лядоўні, пра ежу, як лягушак кідалі ў малако, каб не скісала, пра тое, як выкарыстоўвалі шэрсць вечак, побытавыя прылады, шаптух.',
        ];

        $episodes = $this->reportService->getEpisodes($contents, CategoryType::OTHER);

        $this->assertCount(3, $episodes);

        $this->assertEquals(CategoryType::OTHER, $episodes[0]->getCategory());
        $this->assertEquals(CategoryType::DANCE, $episodes[1]->getCategory());
        $this->assertEquals(CategoryType::CEREMONY, $episodes[2]->getCategory());
    }

    public function testGetEpisodesWithCategory(): void
    {
        $contents = [
            'У дзеньніку асобны аркуш з перапісанымі імёнамі інфарматараў.',
            'Песьні:',
            '"Нападзі раса, раса на цёмны леса"',
            '"... тая пуць-дарожка"',
        ];

        $episodes = $this->reportService->getEpisodes($contents, CategoryType::OTHER);

        $this->assertCount(2, $episodes);

        $this->assertEquals(CategoryType::OTHER, $episodes[0]->getCategory());
        $this->assertEquals(CategoryType::SONGS, $episodes[1]->getCategory());
    }

    public function testGetEpisodesWithJoin(): void
    {
        $contents = [
            'Цікавыя словы, дыялекты:',
            'гавеет - глядзіць на ежу, ды ня есьць',
            'туряць',
            'слончык - малая лавачка',
            'падгалосьнік - той, хто падводзіць у песьні',
            '',
            'Танцавальны этыкет, як трымалі рукі ў парным танцы',
        ];

        $episodes = $this->reportService->getEpisodes($contents, CategoryType::STORY);
        $this->assertNotEmpty($episodes);

        $this->assertCount(2, $episodes);

        $this->assertEquals(CategoryType::OTHER, $episodes[0]->getCategory());
        $this->assertEquals('Цікавыя словы, дыялекты:
гавеет - глядзіць на ежу, ды ня есьць
туряць
слончык - малая лавачка
падгалосьнік - той, хто падводзіць у песьні', $episodes[0]->getText());

    }
}
