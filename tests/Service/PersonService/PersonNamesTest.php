<?php

declare(strict_types=1);

namespace App\Tests\Service\PersonService;

use App\Helper\TextHelper;
use App\Service\PersonService;
use PHPUnit\Framework\TestCase;

class PersonNamesTest extends TestCase
{
    private readonly PersonService $personService;

    public function setUp(): void
    {
        parent::setUp();

        $textHelper = new TextHelper();
        $this->personService = new PersonService($textHelper);
    }

    public function testSuccess3Name(): void
    {
        $name = 'Марозаў Анатолій Уладзіміравіч';

        $isName = $this->personService->isPersonName($name);

        $this->assertTrue($isName);
    }

    public function testBadName(): void
    {
        $name = 'Алена Сцяпанаўна ГетмАнчук';

        $isName = $this->personService->isPersonName($name);

        $this->assertFalse($isName);
    }

    public function testShortName(): void
    {
        $name = 'Замастоцкая';

        $isName = $this->personService->isPersonName($name);

        $this->assertFalse($isName);
    }

    public function testNotName(): void
    {
        $name = 'ансамбль Медуніца';

        $isName = $this->personService->isPersonName($name);

        $this->assertFalse($isName);
    }

    public function testLongName(): void
    {
        $name = 'Алена Данілаўна Гетманчук Вера Гетманчук';

        $isName = $this->personService->isPersonName($name);

        $this->assertFalse($isName);
    }

    public function testSuccess2Name(): void
    {
        $name = 'Марозаў Анатолій';

        $isName = $this->personService->isPersonName($name);

        $this->assertTrue($isName);
    }

    public function testSuccess4Name(): void
    {
        $name = 'Лідзія Мікалаеўна Шуляк (Гетманчук)';

        $isName = $this->personService->isPersonName($name);

        $this->assertTrue($isName);
    }

    public function testSuccessShort2Names(): void
    {
        $name = 'Шуляк Л.М.';

        $isName = $this->personService->isPersonName($name);

        $this->assertTrue($isName);
    }
}
