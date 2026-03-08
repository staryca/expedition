<?php

declare(strict_types=1);

namespace App\Tests\Service\DanceService;

use App\Entity\Dance;
use App\Helper\FileHelper;
use App\Repository\DanceRepository;
use App\Service\DanceService;
use PHPUnit\Framework\TestCase;

class DetectDanceTest extends TestCase
{
    private DanceService $danceService;

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

        $this->danceService = new DanceService($danceRepository);
    }

    /**
     * @dataProvider dataDetectDanceProvider
     * @param string $content
     * @param string|null $expectedDance
     * @return void
     */
    public function testDetectDance(string $content, ?string $expectedDance): void
    {
        $dance = $this->danceService->detectDance($content);
        if ($expectedDance) {
            $this->assertEquals($expectedDance, $dance->getName());
        } else {
            $this->assertNull($dance);
        }
    }

    private function dataDetectDanceProvider(): array
    {
        return [
            ['', null],
            ['Абэрак', 'Абэрак'],
            ['Качан (1)', 'Качан'],
            ['<Качаніе>', null],
            ['Абэрак (інф.: "Мазурка")', 'Абэрак'],
            ['Каробачка і Месяц', 'Каробачка'],
            ['Ножніцы', 'Ножніцы'],
            ['Ножні', 'Ножні'],
        ];
    }
}
