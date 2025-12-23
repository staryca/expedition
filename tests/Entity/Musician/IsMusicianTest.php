<?php

namespace App\Tests\Entity\Musician;

use App\Entity\Additional\Musician;
use PHPUnit\Framework\TestCase;

class IsMusicianTest extends TestCase
{
    /**
     * @dataProvider dataProviderIsMusician
     */
    public function testIsMusician(string $text, bool $isMusician): void
    {
        self::assertEquals($isMusician, Musician::isMusician($text));
    }

    private function dataProviderIsMusician(): array
    {
        return [
            [' ', false],
            ['Свадзебны марш', false],
            ['скрыпка', true],
        ];
    }
}
