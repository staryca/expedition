<?php

declare(strict_types=1);

namespace App\Tests\Service\RitualService;

use App\Service\RitualService;
use PHPUnit\Framework\TestCase;

class TreeFromListTest extends TestCase
{
    public function testTreeFromList(): void
    {
        $list = [
            '#A',
            '#A#B',
            '#A#C',
            '#A#C#D',
            '#A#E',
            '#F',
        ];

        $tree = RitualService::getTreeFromList($list);

        self::assertCount(2, $tree);
        self::assertArrayHasKey('A', $tree);
        self::assertArrayHasKey('F', $tree);
        self::assertCount(3, $tree['A']);
        self::assertArrayHasKey('B', $tree['A']);
        self::assertArrayHasKey('C', $tree['A']);
        self::assertArrayHasKey('E', $tree['A']);
        self::assertCount(1, $tree['A']['C']);
        self::assertArrayHasKey('D', $tree['A']['C']);
        self::assertCount(0, $tree['A']['B']);
        self::assertCount(0, $tree['A']['C']['D']);
        self::assertCount(0, $tree['A']['E']);
    }
}
