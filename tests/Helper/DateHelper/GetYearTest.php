<?php

declare(strict_types=1);

namespace App\Tests\Helper\DateHelper;

use App\Helper\DateHelper;
use PHPUnit\Framework\TestCase;

class GetYearTest extends TestCase
{
    /**
     * @dataProvider dataGetYear
     */
    public function testGetYear(string $text, ?int $expectedYear): void
    {
        $date = DateHelper::getYear($text);

        if ($expectedYear === null) {
            $this->assertNull($date);
        } else {
            $this->assertEquals($expectedYear, $date->year);
            $this->assertEquals(1, $date->month);
            $this->assertEquals(1, $date->day);
        }
    }

    private function dataGetYear(): array
    {
        return [
            ['test', null],
            ['', null],
            ['test_1920', 1920],
            ['test_193', null],
            ['test_209', null],
            ['test_2004', 2004],
            ['test_20_05', null],
            ['test_19 96', null],
            ['test_20_2005', null],
            ['test_2006_test', 2006],
        ];
    }
}

