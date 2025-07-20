<?php

declare(strict_types=1);

namespace App\Tests\Service\PersonService;

use App\Dto\PersonBsuDto;
use App\Helper\TextHelper;
use App\Service\PersonService;
use PHPUnit\Framework\TestCase;

class StudentTest extends TestCase
{
    private readonly PersonService $personService;

    public function setUp(): void
    {
        parent::setUp();

        $textHelper = new TextHelper();
        $this->personService = new PersonService($textHelper);
    }

    public function testDetectStudentsGroup(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'група студэнтаў';

        $students = $this->personService->detectStudents($person);

        $this->assertEmpty($students);
    }

    public function testDetectStudentsGroupP(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'группа студэнтаў';

        $students = $this->personService->detectStudents($person);

        $this->assertEmpty($students);
    }

    public function testDetectStudentsFull2(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Малевіч Людміла Львоўна Ругіневіч Ганна Лявонцьеўна';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(2, $students);
        $this->assertEquals('Малевіч Людміла Львоўна', $students[0]->name);
        $this->assertEquals('Ругіневіч Ганна Лявонцьеўна', $students[1]->name);
    }

    public function testDetectStudentsShort2(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Прылуцкая С. Паўлоўская В.';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(2, $students);
        $this->assertEquals('Прылуцкая С.', $students[0]->name);
        $this->assertEquals('Паўлоўская В.', $students[1]->name);
    }

    public function testDetectStudentsShort3(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Пархімчык Пракапов І.П. Паркоц Т.А.';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(3, $students);
        $this->assertEquals('Пархімчык', $students[0]->name);
        $this->assertEquals('Пракапов І.П.', $students[1]->name);
        $this->assertEquals('Паркоц Т.А.', $students[2]->name);
    }

    public function testDetectStudentsShort1(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Пыжык в. А.';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(1, $students);
        $this->assertEquals('Пыжык В.А.', $students[0]->name);
    }

    public function testDetectStudentsShort1point(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Кляшчонак К.Ф';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(1, $students);
        $this->assertEquals('Кляшчонак К.Ф.', $students[0]->name);
    }

    public function testDetectStudentsLong1(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Дук С. Александровіч';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(1, $students);
        $this->assertEquals('Дук С. Александровіч', $students[0]->name);
    }

    public function testDetectStudentsLLL(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Шах Іван Уладзіміравіч';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(1, $students);
        $this->assertEquals('Шах Іван Уладзіміравіч', $students[0]->name);
    }

    public function testDetectStudentsLL(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Бераснёва Алеся';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(1, $students);
        $this->assertEquals('Бераснёва Алеся', $students[0]->name);
    }

    public function testDetectStudentsShort2i(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Руткоўская Т. і Тулейка В.';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(2, $students);
        $this->assertEquals('Руткоўская Т.', $students[0]->name);
        $this->assertEquals('Тулейка В.', $students[1]->name);
    }

    public function testDetectStudentsMiddle2i(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Малая Жанна і Карамазіна Наталля';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(2, $students);
        $this->assertEquals('Малая Жанна', $students[0]->name);
        $this->assertEquals('Карамазіна Наталля', $students[1]->name);
    }

    public function testDetectStudentsMiddle2iRu(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Стральцова Н. и Чарапаха І.';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(2, $students);
        $this->assertEquals('Стральцова Н.', $students[0]->name);
        $this->assertEquals('Чарапаха І.', $students[1]->name);
    }

    public function testDetectStudentsLong2i(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Барысав Аляксандра Барысаўна і Гаварская Ганна Эдуардаўна';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(2, $students);
        $this->assertEquals('Барысав Аляксандра Барысаўна', $students[0]->name);
        $this->assertEquals('Гаварская Ганна Эдуардаўна', $students[1]->name);
    }

    public function testDetectStudentsShort3name(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Дубатоўка Л. Власава І. Аляхновіч Д.';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(3, $students);
        $this->assertEquals('Дубатоўка Л.', $students[0]->name);
        $this->assertEquals('Власава І.', $students[1]->name);
        $this->assertEquals('Аляхновіч Д.', $students[2]->name);
    }

    public function testDetectStudentsShort4(): void
    {
        $person = new PersonBsuDto();
        $person->name = 'Буланавай Т.У. Белая Т.І. Качаткова Д.Д. Варабей Л.М.';

        $students = $this->personService->detectStudents($person);

        $this->assertCount(4, $students);
        $this->assertEquals('Буланавай Т.У.', $students[0]->name);
        $this->assertEquals('Белая Т.І.', $students[1]->name);
        $this->assertEquals('Качаткова Д.Д.', $students[2]->name);
        $this->assertEquals('Варабей Л.М.', $students[3]->name);
    }
}
