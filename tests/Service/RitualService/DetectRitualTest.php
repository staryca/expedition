<?php

declare(strict_types=1);

namespace App\Tests\Service\RitualService;

use App\Entity\Ritual;
use App\Repository\RitualRepository;
use App\Service\RitualService;
use PHPUnit\Framework\TestCase;

class DetectRitualTest extends TestCase
{
    private readonly RitualService $ritualService;
    private readonly RitualRepository $ritualRepository;

    public function setUp(): void
    {
        $this->ritualRepository = $this->createMock(RitualRepository::class);
        $this->ritualService = new RitualService($this->ritualRepository);
    }

    /**
     * @dataProvider dataRituals
     * @param string $label
     * @param array<Ritual> $rituals
     * @param bool $expected
     * @return void
     */
    public function testDetectRitual(string $label, array $rituals, bool $expected): void
    {
        $this->ritualRepository->expects($this->exactly(1))
            ->method('findAll')
            ->willReturn($rituals);

        $result = $this->ritualService->findRitual($label);

        $this->assertEquals($expected, $result !== null);
    }

    private static function dataRituals(): iterable
    {
        $ritualA = new Ritual();
        $ritualA->setName('A');

        $ritualB = new Ritual();
        $ritualB->setName('B');
        $ritualB->setParent($ritualA);

        yield ['#A', [$ritualA], true];
        yield ['#B', [$ritualA], false];
        yield ['#B', [$ritualA, $ritualB], true];
        yield ['#A#B', [$ritualA, $ritualB], true];
        yield ['#A#B#C', [$ritualA, $ritualB], false];
    }
}
