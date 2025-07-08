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
}
