<?php

declare(strict_types=1);

namespace App\Tests\Service\PersonService;

use App\Dto\NameGenderDto;
use App\Entity\Type\GenderType;
use App\Service\PersonService;
use PHPUnit\Framework\TestCase;

class NameAndGenderTest extends TestCase
{
    private readonly PersonService $personService;

    public function setUp(): void
    {
        parent::setUp();

        $this->personService = new PersonService();
    }

    public function testUniqueNames(): void
    {
        $duplicates = GenderType::getNotUniqueNames();

        $this->assertEmpty($duplicates, 'Duplicate names: ' . print_r($duplicates, true));
    }

    /**
     * @dataProvider dataNamesAndGenders
     */
    public function testDetectNameAndGender(string $name, string $expectedName, int $expectedGender, bool $isSameNames): void
    {
        $dto = new NameGenderDto($name);

        $this->personService->fixNameAndGender($dto);

        $this->assertEquals($expectedName, $dto->getName());
        $this->assertEquals($expectedGender, $dto->gender, 'Bad gender for ' . $name);
        if ($isSameNames) {
            $this->assertTrue($this->personService->isSameNames($name, $dto->getName()), 'Not same names: ' . print_r($dto, true));
        }
    }

    private function dataNamesAndGenders(): array
    {
        return [
            ['', '', GenderType::UNKNOWN, false],
            ['бацька жанчыны', 'бацька жанчыны', GenderType::UNKNOWN, false],
            ['Яскевіч Алена Аляксандраўна', 'Яскевіч Алена Аляксандраўна', GenderType::FEMALE, true],
            ['Лідзія Мікалаеўна Шуляк', 'Шуляк Лідзія Мікалаеўна', GenderType::FEMALE, true],
            ['Лук\'янава (дзявочае?)', 'Лук\'янава (дзявочае?)', GenderType::FEMALE, true],
            ['Вольга Фядосаўна Жыбула (дзяв. Грачыха)', 'Жыбула (дзяв. Грачыха) Вольга Фядосаўна', GenderType::FEMALE, true],
            ['Василевич Ирина Николевна', 'Васілевіч Ірына Мікалаеўна', GenderType::FEMALE, false],
            ['Саўкова Раіса <Ў>ласаўна', 'Саўкова Раіса <Ў>ласаўна', GenderType::FEMALE, true],
            ['Лахцеева  (Канбасава)   Валянціна  Фёдараўна', 'Лахцеева (Канбасава) Валянціна Фёдараўна', GenderType::FEMALE, true],
            ['Сасноўская Надзея', 'Сасноўская Надзея', GenderType::FEMALE, true],
            ['Георгіеўна Ганна Івакова', 'Івакова Ганна Георгіеўна', GenderType::FEMALE, true],
            ['Даражынскі М.Ю.', 'Даражынскі М.Ю.', GenderType::MALE, true],
            ['Станіслаў Вікеньцевіч ("Вікентавіч") Маленчык', 'Маленчык Станіслаў Вікеньцевіч ("Вікентавіч")', GenderType::MALE, true],
            ['Русіна Людзміла А.', 'Русіна Людзміла А.', GenderType::FEMALE, true],
            ['Романович Василий Никитович', 'Романовіч Васіль Мікітавіч', GenderType::MALE, false],
            ['Леанід Мамахавец Гарбацэвіч ', 'Мамахавец Леанід Гарбацэвіч', GenderType::MALE, true],
            ['Бондар Игнат Иванович', 'Бондар Ігнат Івановіч', GenderType::MALE, false],
            ['Кавалёва (Новікава) Вера (Верачка) Лявонаўна', 'Кавалёва (Новікава) Вера (Верачка) Лявонаўна', GenderType::FEMALE, true],
            ['Надзея Іванаўна Фёдарава (Етава)', 'Фёдарава (Етава) Надзея Іванаўна', GenderType::FEMALE, true],
            ['З. Пышко', 'Пышко З.', GenderType::UNKNOWN, false],
            ['Міхайлавіч Уладзімір Паўлюк', 'Паўлюк Уладзімір Міхайлавіч', GenderType::MALE, true],
            ['Беўза Валерка Лявонаўна', 'Беўза Валерка Лявонаўна', GenderType::FEMALE, true],
            ['Антаніна Самуілаўна <К(ы)>сьцянка', '<К(ы)>сьцянка Антаніна Самуілаўна', GenderType::FEMALE, true],
            ['Ж. Леановіч', 'Леановіч Ж.', GenderType::UNKNOWN, false],
            ['Васілеўская А.', 'Васілеўская А.', GenderType::FEMALE, true],
            ['Кірык В.У.', 'Кірык В.У.', GenderType::UNKNOWN, false],
            ['Пётр Сяргеевіч', 'Пётр Сяргеевіч', GenderType::MALE, true],
            ['Марына Хаймовіч', 'Хаймовіч Марына', GenderType::FEMALE, true],
            ['Тарасюк', 'Тарасюк', GenderType::UNKNOWN, false],
            ['Тарас', 'Тарас', GenderType::MALE, false],
            ['Івандзілава Вольга Кулрыёнава', 'Івандзілава Вольга Кулрыёнава', GenderType::FEMALE, true],
            ['Яніна Сіройць (Казючыха)', 'Сіройць (Казючыха) Яніна', GenderType::FEMALE, true],
            ['Козенка Мікола Аляксеевіч', 'Козенка Мікола Аляксеевіч', GenderType::MALE, true],
            ['Савіч М.І.', 'Савіч М.І.', GenderType::UNKNOWN, false],
            ['Савіч Мікола І.', 'Савіч Мікола І.', GenderType::MALE, false],
            ['Трафімаўна Марына Рыгораўна', 'Трафімаўна Марына Рыгораўна', GenderType::FEMALE, true],
            ['Клімовіч Ніна Ільінічна', 'Клімовіч Ніна Ільінічна', GenderType::FEMALE, true],
            ['Уладзіслава Пячкур', 'Пячкур Уладзіслава', GenderType::FEMALE, true],
            ['Анісся Кірачка', 'Кірачка Анісся', GenderType::FEMALE, true],
        ];
    }
}
