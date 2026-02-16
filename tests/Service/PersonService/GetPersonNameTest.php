<?php

declare(strict_types=1);

namespace App\Tests\Service\PersonService;

use App\Entity\Type\GenderType;
use App\Service\PersonService;
use PHPUnit\Framework\TestCase;

class GetPersonNameTest extends TestCase
{
    private readonly PersonService $personService;

    public function setUp(): void
    {
        parent::setUp();

        $this->personService = new PersonService();
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

    public function testSuccessWithBirth(): void
    {
        $name = 'жонка Чыгілейчык Марыя Рыгораўна 1897 г.н.';

        $informant = $this->personService->getPersonByFullName($name);

        $this->assertNotNull($informant);
        $this->assertEquals('Чыгілейчык Марыя Рыгораўна', $informant->name);
        $this->assertEquals(1897, $informant->birth);
        $this->assertEquals('жонка', $informant->notes);
    }

    public function testSuccessWithBirthAndNotes(): void
    {
        $name = 'Бовіч Акуліна Адамаўна 1927 г.н., працуе ў КБО';

        $informant = $this->personService->getPersonByFullName($name);

        $this->assertNotNull($informant);
        $this->assertEquals('Бовіч Акуліна Адамаўна', $informant->name);
        $this->assertEquals(1927, $informant->birth);
        $this->assertEquals('працуе ў КБО', $informant->notes);
    }

    public function testSuccessWithBadBirth(): void
    {
        $name = 'Сакаловы Аленай 1920 г. 5 кл.';

        $informant = $this->personService->getPersonByFullName($name, null, true);

        $this->assertNotNull($informant);
        $this->assertEquals('Сакалова Алена', $informant->name);
        $this->assertEquals(1920, $informant->birth);
        $this->assertEquals('5 кл', $informant->notes);
    }

    public function testSuccessWithLastAsMiddle(): void
    {
        $name = 'Гапановіча Генадзя';

        $informant = $this->personService->getPersonByFullName($name, null, true);

        $this->assertNotNull($informant);
        $this->assertEquals('Гапановіч Генадзь', $informant->name);
        $this->assertNull($informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
    }

    public function testSuccessWithShortBirth(): void
    {
        $name = 'Бут Ціхон 12г';

        $informant = $this->personService->getPersonByFullName($name, 2010, true);

        $this->assertNotNull($informant);
        $this->assertEquals('Бут Ціхон', $informant->name);
        $this->assertEquals(1998, $informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
    }

    public function testSuccessWithLastAsMiddle2(): void
    {
        $name = 'Зміцер Астапук';

        $informant = $this->personService->getPersonByFullName($name, null, true);

        $this->assertNotNull($informant);
        $this->assertEquals('Зміцер Астапук', $informant->name); // "Зміцер" and "Астапук" are names
        $this->assertNull($informant->birth);
        $this->assertEquals(GenderType::MALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
    }

    public function testSuccessWithMaybeBirth(): void
    {
        $name = 'Савасцееў Івана Еўдакімавіча каля 100 гадоў';

        $informant = $this->personService->getPersonByFullName($name, 2008, true);

        $this->assertNotNull($informant);
        $this->assertEquals('Савасцееў Іван Еўдакімавіч', $informant->name);
        $this->assertEquals(1908, $informant->birth);
        $this->assertEquals('', $informant->notes);
    }

    public function testSuccessWithShortNameAndBirth(): void
    {
        $name = 'Пяхота А.К.1908 г.н';

        $informant = $this->personService->getPersonByFullName($name, null, true);

        $this->assertNotNull($informant);
        $this->assertEquals('Пяхота А.К.', $informant->name);
        $this->assertEquals(1908, $informant->birth);
        $this->assertEquals(GenderType::UNKNOWN, $informant->gender);
        $this->assertEquals('', $informant->notes);
    }

    public function testSuccessFrom(): void
    {
        $name = 'ад Раманоўскай Вольгі Сямёнаўны';

        $informant = $this->personService->getPersonByFullName($name);

        $this->assertNotNull($informant);
        $this->assertEquals('Раманоўская Вольга Сямёнаўна', $informant->name);
        $this->assertNull($informant->birth);
        $this->assertEquals(GenderType::FEMALE, $informant->gender);
        $this->assertEquals('', $informant->notes);
    }
}
