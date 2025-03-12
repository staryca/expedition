<?php

declare(strict_types=1);

namespace App\Tests\Service\PersonService;

use App\Helper\TextHelper;
use App\Service\PersonService;
use PHPUnit\Framework\TestCase;

class GetPersonNameTest extends TestCase
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
        $name = 'выкладчык Марозаў Анатолій Уладзіміравіч.';

        $informant = $this->personService->getPersonByFullName($name);

        $this->assertNotNull($informant);
        $this->assertEquals('Марозаў Анатолій Уладзіміравіч', $informant->name);
        $this->assertEquals('выкладчык', $informant->notes);
    }

    public function testSuccessManyName(): void
    {
        $name = 'Загадчыца Беразлянскага сельскага клубу Шуляк Лідзія Мікалаеўна, жанчыны мясцовага калектыву';

        $informant = $this->personService->getPersonByFullName($name);

        $this->assertNotNull($informant);
        $this->assertEquals('Шуляк Лідзія Мікалаеўна', $informant->name);
        $this->assertEquals('Загадчыца Беразлянскага сельскага клубу, жанчыны мясцовага калектыву', $informant->notes);
    }

    public function testSuccessWithBrackets(): void
    {
        $name = 'жонка Ніна Яўсееўна Хомчанка (Жукава)';

        $informant = $this->personService->getPersonByFullName($name);

        $this->assertNotNull($informant);
        $this->assertEquals('Хомчанка (Жукава) Ніна Яўсееўна', $informant->name);
        $this->assertEquals('жонка', $informant->notes);
    }
}
