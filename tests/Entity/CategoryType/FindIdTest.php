<?php

declare(strict_types=1);

namespace App\Tests\Entity\CategoryType;

use App\Entity\Type\CategoryType;
use PHPUnit\Framework\TestCase;

class FindIdTest extends TestCase
{
    public function testFindId(): void
    {
        foreach (CategoryType::TYPES as $type => $category) {
            $this->assertEquals(
                $type,
                CategoryType::findId($category, ''),
                sprintf('Error findId() for "%s". Type is %d.', $category, $type)
            );
        }
    }
}
