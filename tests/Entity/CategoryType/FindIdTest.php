<?php

declare(strict_types=1);

namespace App\Tests\Entity\CategoryType;

use App\Entity\Type\CategoryType;
use PHPUnit\Framework\TestCase;

class FindIdTest extends TestCase
{
    public function testFindIdSingle(): void
    {
        foreach (CategoryType::TYPES as $type => $category) {
            $this->assertEquals(
                $type,
                CategoryType::findId($category, ''),
                sprintf('Error findId() for "%s". Type is %d.', $category, $type)
            );
        }
    }

    public function testFindIdMany(): void
    {
        foreach (CategoryType::TYPES_MANY as $type => $category) {
            if (null !== $category) {
                $this->assertEquals(
                    $type,
                    CategoryType::findId($category, ''),
                    sprintf('Error findId() for "%s". Type is %d.', $category, $type)
                );
            }
        }
    }

    /**
     * @dataProvider dataProviderFindIdTexts
     */
    public function testFindIdText(string $text, int $expectedId): void
    {
        self::assertEquals($expectedId, CategoryType::findId($text, ''));
    }

    private function dataProviderFindIdTexts(): array
    {
        return [
            ['прыпеўкі і мелодыя танца', CategoryType::CHORUSES],
            ['прыпеўка, пяе', CategoryType::CHORUSES],
            ['прыпеўка да полькі Тэрніца', CategoryType::CHORUSES],
            ['прыпеўкі пад акампанемент квінтэта', CategoryType::CHORUSES],
            ['найгрыш на скрыпцы', CategoryType::MELODY],
            ['найгрыш з прыпеўкамі', CategoryType::MELODY],
            ['на язык', CategoryType::MELODY],
            ['жартоўная песня, пад гітару', CategoryType::SONGS],
        ];
    }
}
