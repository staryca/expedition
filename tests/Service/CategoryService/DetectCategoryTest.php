<?php

declare(strict_types=1);

namespace App\Tests\Service\CategoryService;

use App\Entity\Dance;
use App\Entity\Type\CategoryType;
use App\Helper\FileHelper;
use App\Repository\DanceRepository;
use App\Service\CategoryService;
use App\Service\DanceService;
use PHPUnit\Framework\TestCase;

class DetectCategoryTest extends TestCase
{
    private CategoryService $categoryService;

    public function setUp(): void
    {
        parent::setUp();

        $dances = FileHelper::getArrayFromFile('src/DataFixtures/dances.csv');
        $objects = [];
        foreach ($dances as $dance) {
            $object = new Dance();
            $object->setName($dance);
            $objects[] = $object;
        }

        $danceRepository = $this->createMock(DanceRepository::class);
        $danceRepository->method('findAll')->willReturn($objects);

        $danceService = new DanceService($danceRepository);
        $this->categoryService = new CategoryService($danceService);
    }

    /**
     * @dataProvider dataCategoriesProvider
     */
    public function testDetectCategory(string $content, string $notes, ?int $expectedCategory): void
    {
        $category = $this->categoryService->detectCategory($content, $notes);
        $this->assertEquals($expectedCategory, $category, 'Error for "' . $content . '"');
    }

    private function dataCategoriesProvider(): array
    {
        return [
            ['', '', null],
            ['"Первым разам добрым часам"', 'замова, як першы раз выганяюць карову', CategoryType::SPELL],
            ['"Пашоў коцік на таржок"', 'калыханка, пяе', CategoryType::LULLABY],
            ['Абэрка', 'скрыпка', CategoryType::MELODY],
            ['Старынныя страданія пад прыпеўкі', 'гармонік, скрыпка', CategoryType::CHORUSES],
            ['Якія былі танцы', 'каробушка, полька, вальс, кадрыля (12 каленаў)', CategoryType::ABOUT_DANCES],
            ['Якія танцы гулялі', 'полька, кадрыль, раскамарыцкі, барыня, люлька', CategoryType::ABOUT_DANCES],
            ['"Як пайду я ў клець па муку"', 'прыпеўка да 4-га калена кадрылі, пяе', CategoryType::CHORUSES],
            ['"З ахотніка й музыкі гаспадар невялікі"', 'прыказка', CategoryType::PAREMIA],
            ['', 'казка', CategoryType::FAIRY_TALE],
            ['', 'верш', CategoryType::POEMS],
            ['Гукарад', '"Вершнік" (ракаўская цацка, 1920-я г.г.)', CategoryType::MELODY],
            ['"Сягоння субота, а заўтра нядзеля. Чаго ў цябе, хлопча, кашуля нябела"', 'інф.: застольная, гармонік', CategoryType::SONGS],
            ['"А в сій хаты есць шо даты"', 'шчадроўка, як пачаставалі, пяе', CategoryType::SONGS],
            ['Адхадны марш (1)', 'ансамбль', CategoryType::MELODY],
            ['"Баба-Яга хоча сустрэцца з Лешым"', 'анекдот', CategoryType::STORY],
            ['Да пятрова ночка маленька, да не выспалася паненка', 'узгадка купальскай песні', CategoryType::SONGS],
            ['Сігнал "Сняданне"', 'труба', CategoryType::MELODY],
            ['Звесткі пра інфарманта', '', CategoryType::ABOUT_INFORMANT],
            ['(Зьвесткі пра запіс)', '', CategoryType::ABOUT_RECORD],
            ['Як запрашалі на танец дзяўчыну, як трымаліся?', 'апускаў дзьве рукі далонямі ўгару', CategoryType::STORY],
            ['Пра танец "Дустэп"', '', CategoryType::ABOUT_DANCES],
            ['Да́мскі вальс', '', CategoryType::MELODY],
            ['Кадрыль (1-ы танец)', '', CategoryType::QUADRILLE],
            ['Як разводзяць танец на вісіллі', '', CategoryType::STORY],
        ];
    }
}
