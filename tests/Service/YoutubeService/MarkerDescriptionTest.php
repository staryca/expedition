<?php

namespace App\Tests\Service\YoutubeService;

use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\FileMarker;
use App\Entity\Type\CategoryType;
use App\Helper\TextHelper;
use App\Repository\FileMarkerRepository;
use App\Service\YoutubeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MarkerDescriptionTest extends TestCase
{
    private YoutubeService $service;

    public function setUp(): void
    {
        $this->service = new YoutubeService(
            '',
            new TextHelper(),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(RequestStack::class),
            $this->createMock(FileMarkerRepository::class)
        );
    }

    /**
     * @dataProvider dataMarkers
     */
    public function testMarkerDescription(string $exceptedDescription, int $category, array $additional): void
    {
        $marker = new FileMarker();
        $marker->setCategory($category);
        $marker->setAdditional($additional);

        $description = $this->service->getMarkerDescription($marker);
        self::assertEquals($exceptedDescription, $description);
    }

    private function dataMarkers(): array
    {
        return [
            ['Пазаабрадавы танец "Карапет" з устойлівай кампазіцыяй', CategoryType::DANCE, [
                FileMarkerAdditional::LOCAL_NAME => 'Карапет',
            ]],
            ['Песня "Гоп, мае чаравічкі"', CategoryType::SONGS, [
                FileMarkerAdditional::LOCAL_NAME => 'Гоп, мае чаравічкі',
            ]],
            ['Танец-гульня "Танец з хустачкай"', CategoryType::DANCE_GAME, [
                FileMarkerAdditional::LOCAL_NAME => 'Танец з хустачкай',
            ]],
            ['Рухі танца "Ойра"', CategoryType::DANCE_MOVEMENTS, [
                FileMarkerAdditional::LOCAL_NAME => 'Ойра',
            ]],
            ['Абрад "Вынас каравая"', CategoryType::CEREMONY, [
                FileMarkerAdditional::LOCAL_NAME => 'Вынас каравая',
            ]],
            ['Найгрыш "Полька"', CategoryType::MELODY, [
                FileMarkerAdditional::LOCAL_NAME => 'Полька',
            ]],

            ['Пазаабрадавы імправізацыйны сольны танец', CategoryType::DANCE, [
                FileMarkerAdditional::IMPROVISATION => 'імправізацыйны',
                FileMarkerAdditional::DANCE_TYPE => 'сольны',
            ]],
            ['Рухі сольнага мужчынскага імправізацыйнага танца', CategoryType::DANCE_MOVEMENTS, [
                FileMarkerAdditional::IMPROVISATION => 'імправізацыйны',
                FileMarkerAdditional::DANCE_TYPE => 'сольны мужчынскі',
            ]],
        ];
    }
}
