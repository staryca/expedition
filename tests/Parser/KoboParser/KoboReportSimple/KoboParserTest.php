<?php

declare(strict_types=1);

namespace App\Tests\Parser\KoboParser\KoboReportSimple;

use App\Entity\Type\GenderType;
use App\Entity\Type\InformationType;
use App\Entity\Type\ReportBlockType;
use App\Helper\TextHelper;
use App\Parser\KoboParser;
use App\Repository\GeoPointRepository;
use App\Repository\UserRepository;
use App\Service\LocationService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class KoboParserTest extends TestCase
{
    private readonly KoboParser $koboParser;
    private readonly GeoPointRepository $geoPointRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->geoPointRepository = $this->createMock(GeoPointRepository::class);

        $textHelper = new TextHelper();
        $locationService = new LocationService($this->geoPointRepository, $textHelper);
        $userService = new UserService(
            $this->createMock(UserRepository::class), $textHelper
        );
        $this->koboParser = new KoboParser($locationService, $userService);
    }

    public function testParseInformants(): void
    {
        $filename = __DIR__ . '/informants.csv';
        $content = file_get_contents($filename);

        $informants = $this->koboParser->parseInformants($content);

        $this->assertCount(2, $informants);

        $informant = $informants[38];
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('Навуменка Міхаіл Рыгоравіч', $informant->name);
        $this->assertEquals(1939, $informant->birth);
        $this->assertEquals('праваслаўе', $informant->confession);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Сасновая Наспа, Лёзненскі раён (Крынковский c/c)', $informant->locations[0]);
        $this->assertCount(1, $informant->codeReports);
        $this->assertEquals(46, $informant->codeReports[0]);
        $this->assertEquals('2021-08-13', $informant->dateAdded->format('Y-m-d'));

        $informant = $informants[39];
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('Навуменка (Ражкова) Фаіна Цімафееўна', $informant->name);
        $this->assertEquals(1944, $informant->birth);
        $this->assertEquals('праваслаўе', $informant->confession);
        $this->assertCount(1, $informant->locations);
        $this->assertEquals('в. Ордзеж, Лёзненскі раён (Крынковский c/c)', $informant->locations[0]);
        $this->assertEquals('нарадзілася ў бежанстве, Лёдцы Віцебскага р-на, сям\'я з в. Ордзеж; жонка Навуменкі М. Р.', $informant->notes);
        $this->assertCount(1, $informant->codeReports);
        $this->assertEquals(46, $informant->codeReports[0]);
        $this->assertEquals('2021-08-13', $informant->dateAdded->format('Y-m-d'));
    }

    public function testParseOrganizations(): void
    {
        $filename = __DIR__ . '/organizations.csv';
        $content = file_get_contents($filename);

        $organizations = $this->koboParser->parseOrganizations($content);

        $this->assertCount(1, $organizations);

        $organization = $organizations[2];
        $this->assertEquals('ДУК «Лёзненскі раённы Дом рамёстваў»', $organization->name);
        $this->assertEquals('г.п. Лёзна, вул. Леніна, 65', $organization->notes);
        $this->assertCount(1, $organization->codeReports);
        $this->assertEquals(49, $organization->codeReports[0]);
        $this->assertEquals('2021-08-13', $organization->dateAdded->format('Y-m-d'));
    }

    public function testParseReports(): void
    {
        $filename = __DIR__ . '/reports.csv';
        $content = file_get_contents($filename);

        $reports = $this->koboParser->parseReports($content);

        $this->assertCount(4, $reports);

        $report = $reports[46];
        $this->assertEquals('20210813_Kruk_01_Ордзеж_01', $report->code);
        $this->assertEquals('в. Ордзеж, Лёзненскі раён (Крынковский c/c)', $report->geoNotes);
        $this->assertEquals('2021-08-13', $report->dateCreated->format('Y-m-d'));
        $this->assertEquals('2021-08-13', $report->dateAction->format('Y-m-d'));
        $this->assertCount(2, $report->users);
        $this->assertCount(1, $report->blocks);
        $this->assertEquals(ReportBlockType::TYPE_CONVERSATION, $report->blocks[0]->type);
        $this->assertCount(2, $report->blocks[0]->additional);
        $this->assertArrayHasKey(InformationType::AUDIO, $report->blocks[0]->additional);
        $this->assertArrayHasKey(InformationType::PHOTO, $report->blocks[0]->additional);
        $this->assertEquals('гаспадарчыя пабудовы "астроўкі" (азярод), партрэт інфарматараў', $report->blocks[0]->photoNotes);

        $report = $reports[47];
        $this->assertEquals('20210813_Kruk_01_Ордзеж_02', $report->code);
        $this->assertEquals('в. Ордзеж, Лёзненскі раён (Крынковский c/c)', $report->geoNotes);
        $this->assertEquals('2021-08-13', $report->dateCreated->format('Y-m-d'));
        $this->assertEquals('2021-08-13', $report->dateAction->format('Y-m-d'));
        $this->assertCount(2, $report->users);
        $this->assertCount(1, $report->blocks);
        $this->assertEquals('Наведаць могілкі в. Ордзеж (і Глінкі).', $report->blocks[0]->description);
        $this->assertEquals(ReportBlockType::TYPE_CONVERSATION, $report->blocks[0]->type);
        $this->assertCount(2, $report->blocks[0]->additional);
        $this->assertArrayHasKey(InformationType::AUDIO, $report->blocks[0]->additional);
        $this->assertArrayHasKey(InformationType::PHOTO, $report->blocks[0]->additional);
        $this->assertEquals('партрэт інфарматаркі, копіі фотаздымкаў з сямейнага архіва: "прашчальная" (провады) сына Сяргея, з гармонікам Мікалай Купрэеў (№2), пераапранутыя госці з вяселля сына Сяргея, 1970 г. (№3-4), сын Сяргей у дзяцінстве (№5),   гарманіст Уладзімір Ражкоў (№5-7), дзед інф-кі Васіль Фёдаравіч Семянкоў з жонкай (№8), свякры інф-кі Макар і Лукер\'я Хадарцовы (№9), партрэты інфарматаркі і мужа (№ 10-11)',
            $report->blocks[0]->photoNotes
        );

        $report = $reports[48];
        $this->assertEquals('1-01', $report->code);
        $this->assertEquals('гп Лёзна, Лёзненскі раён, Віцебская вобласць, Беларусь', $report->geoNotes);
        $this->assertEquals('2021-08-13', $report->dateCreated->format('Y-m-d'));
        $this->assertEquals('2021-08-13', $report->dateAction->format('Y-m-d'));
        $this->assertEquals('55.019993', $report->lat);
        $this->assertEquals('30.795776', $report->lon);
        $this->assertCount(3, $report->users);
        $this->assertCount(1, $report->blocks);
        $this->assertEquals(ReportBlockType::TYPE_PHOTO_OF_ITEMS, $report->blocks[0]->type);
        $this->assertCount(1, $report->blocks[0]->additional);
        $this->assertArrayHasKey(InformationType::PHOTO, $report->blocks[0]->additional);
        $this->assertEquals('Фота  рушнікоў, кашуль, андарака, посцілак, куфраў, збаноў, прасоў, жорны, свістуляк   А.М. Траяноўскага , скрыпка  А.Г. Антонава', $report->blocks[0]->photoNotes);

        $report = $reports[49];
        $this->assertEquals('1-02', $report->code);
        $this->assertEquals('гп Лёзна, Лёзненскі раён, Віцебская вобласць, Беларусь', $report->geoNotes);
        $this->assertEquals('2021-08-13', $report->dateCreated->format('Y-m-d'));
        $this->assertEquals('2021-08-13', $report->dateAction->format('Y-m-d'));
        $this->assertEquals('55.020622', $report->lat);
        $this->assertEquals('30.795776', $report->lon);
        $this->assertCount(3, $report->users);
        $this->assertCount(1, $report->blocks);
        $this->assertEquals(ReportBlockType::TYPE_PHOTO_OF_ITEMS, $report->blocks[0]->type);
        $this->assertCount(1, $report->blocks[0]->additional);
        $this->assertArrayHasKey(InformationType::PHOTO, $report->blocks[0]->additional);
        $this->assertEquals('фота  вырабаў мясцовых майстрых з лямцу, з гліны, з лазы і саломы, тэкстыльныя вырабы (самаробныя строі, паясы), карціны.', $report->blocks[0]->photoNotes);
    }

    public function testParseContents(): void
    {
        $filename = __DIR__ . '/contents.csv';
        $content = file_get_contents($filename);

        $contents = $this->koboParser->parseContents($content);

        $this->assertCount(4, $contents);

        $content = $contents[104];
        $this->assertEquals(46, $content->reportIndex);
        $this->assertEquals('Біяграфічныя звесткі. Як мясцовыя хутары звозілі ў вёску ў 1939 г.',
            mb_substr($content->notes, 0, 66)
        );

        $content = $contents[105];
        $this->assertEquals(47, $content->reportIndex);
        $this->assertEquals('Біяграфічныя звесткі.  Вяселле, пераезд у Ордзеж.  Мясцовыя музыканты:  Мікалай Смолаў',
            mb_substr($content->notes, 0, 86)
        );

        $content = $contents[106];
        $this->assertEquals(48, $content->reportIndex);
        $this->assertEquals('Фота  рушнікоў, кашуль, андарака, посцілак, куфраў, збаноў, прасоў, жорны, свістуляк',
            mb_substr($content->notes, 0, 84)
        );

        $content = $contents[107];
        $this->assertEquals(49, $content->reportIndex);
        $this->assertEquals('фота  вырабаў мясцовых майстрых з лямцу, з гліны, з лазы і саломы, тэкстыльныя вырабы',
            mb_substr($content->notes, 0, 85)
        );
    }

    public function testParseTags(): void
    {
        $filename = __DIR__ . '/tags.csv';
        $content = file_get_contents($filename);

        $tags = $this->koboParser->parseTags($content);

        $this->assertCount(4, $tags);

        $this->assertCount(9, $tags[104]);
        $this->assertCount(9, $tags[105]);
        $this->assertCount(4, $tags[106]);
        $this->assertCount(5, $tags[107]);

        $this->assertEquals('тэкстыль', $tags[107][0]);
        $this->assertEquals('кераміка', $tags[107][1]);
        $this->assertEquals('саломапляценне', $tags[107][2]);
        $this->assertEquals('лозапляценне', $tags[107][3]);
        $this->assertEquals('батлейка', $tags[107][4]);
    }
}
