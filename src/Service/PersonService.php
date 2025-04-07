<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\InformantDto;
use App\Dto\NameGenderDto;
use App\Dto\OrganizationDto;
use App\Dto\PersonBsuDto;
use App\Dto\StudentDto;
use App\Entity\Type\GenderType;
use App\Helper\TextHelper;
use Carbon\Carbon;

class PersonService
{
    private const NUM_UNDEFINED = 0;
    private const NUM_LAST = 1;
    private const NUM_FIRST = 2;
    private const NUM_MIDDLE = 3;
    private const NUM_FIRST_MIDDLE = 4;

    public function __construct(
        private readonly TextHelper $textHelper,
    ) {
    }

    public function parseOrganization(OrganizationDto $dto): void
    {
        $name = trim($dto->name, " ;,\t\n\r\0\x0B");
        $name = TextHelper::replaceLetters($name);
        $name = TextHelper::cleanManySpaces($name);

        $pos = mb_strpos($name, ':');
        $posBlock = mb_strpos($name, ';');
        if ($pos !== false && (false === $posBlock || $posBlock > $pos)) {
            $text = trim(mb_substr($name, 0, $pos));
            $persons = trim(mb_substr($name, $pos + 1));

            // A, b, C, d. Word: E, f, G, h => (A, b, C, d) + (E, f, G, h)(Word)
            $posSpace = mb_strrpos($text, ' ');
            $char = $posSpace !== false ? mb_substr($text, $posSpace - 1, 1) : null;
            if (in_array($char, ['.', ';'])) {
                $textFirst = trim(mb_substr($text, 0, $posSpace));
                $word = mb_substr($text, $posSpace + 1);

                $informantsFirst = $this->getInformants($textFirst);
                if (count($informantsFirst) > 1) {
                    $dto->informants = array_merge(
                        $informantsFirst,
                        $this->getInformants($persons, mb_strtolower($word))
                    );
                    $dto->name = '';
                    return;
                }
            }

            $pos = mb_strpos($persons, 'кір.');
            if (false !== $pos && !str_contains($persons, ',')) {
                $informant = new InformantDto();
                $informant->notes = 'кір.';
                $informant->name = preg_replace(
                    '!\s+!',
                    ' ',
                    trim(mb_substr($persons, 0, $pos) . mb_substr($persons, $pos + 4))
                );
                $dto->informants[] = $informant;
            } else {
                $dto->informants = $this->getInformants($persons);
            }
        } elseif (str_contains($name, 'г.н.')) {
            $dto->informants = $this->getInformants($name);
            $text = '';
        } else {
            $text = $name;
        }

        // Parse the name
        $informant = $this->getPersonByFullName($text);
        if (null !== $informant) {
            $parts = explode(',', $text);
            $name = '';
            if (count($parts) < 2) {
                $this->addInformants($dto->informants, $informant);
            } else {
                $notes = [];
                $informant = null;
                foreach ($parts as $part) {
                    if (null === $informant) {
                        $informant = $this->getPersonByFullName($part);
                        if (null !== $informant) {
                            $this->addInformants($dto->informants, $informant);
                            continue;
                        }
                    }
                    if (empty($name)) {
                        $name = trim($part, " .,;\t\n\r\0\x0B");
                    } else {
                        $notes[] = $part;
                    }
                }
                if (!empty($notes)) {
                    $dto->notes = implode(',', $notes);
                }
            }
        } else {
            // Name as person with gender
            $informant = null;
            [$person, $notes] = $this->textHelper->getNotes($text);
            $parts = explode(' ', trim($person));
            if (count($parts) === 2) {
                $nameGenderDto = new NameGenderDto($person);
                $this->fixNameAndGender($nameGenderDto);
                if ($nameGenderDto->gender !== GenderType::UNKNOWN) {
                    $informant = new InformantDto();
                    $informant->setNameAndGender($nameGenderDto);
                    $informant->addNotes($notes);

                    $this->addInformants($dto->informants, $informant);
                }
            }

            $name = (null === $informant) ? $text : '';
        }

        $dto->name = trim($name, " .,;\t\n\r\0\x0B");
    }

    public function isPersonName(string $name): bool
    {
        $name = trim(str_replace(['(', ')', ';', ',', '.'], ['', '', '', '', '. '], $name));
        $name = preg_replace('!\s+!', ' ', $name);

        $parts = explode(' ', $name);
        if (count($parts) < 2 || count($parts) > 4) {
            return false;
        }

        foreach ($parts as $part) {
            if (!$this->textHelper->isName($part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<InformantDto> $informants
     * @param InformantDto ...$newInformants
     * @return void
     */
    public function addInformants(array &$informants, InformantDto ...$newInformants): void
    {
        foreach ($newInformants as $newInformant) {
            foreach ($informants as $informant) {
                if ($this->isSameNames($informant->name, $newInformant->name)) {
                    $informant->name = $this->getFullName($informant->name, $newInformant->name);
                    $informant->mergeInformant($newInformant);
                    return;
                }
            }

            $informants[] = $newInformant;
        }
    }

    /**
     * Sample: f d y g F K F f q a => person: F K F
     * @param string $name
     * @return InformantDto|null
     */
    public function getPersonByFullName(string $name): ?InformantDto
    {
        $parts = explode(' ', trim($name));
        if (count($parts) < 3) {
            return null;
        }

        $partsName = [];
        $partsNote = [];
        $informant = null;
        foreach ($parts as $part) {
            if (!$informant && $this->textHelper->isNameWithBrackets($part)) {
                $last = mb_substr($part, -1);
                if (mb_strlen($part) > 2 && in_array($last, [',', ':', '.', ';', '-'])) {
                    if (count($partsName) >= 2) {
                        $part = mb_substr($part, 0, -1);
                        $partsNote[] = $last;
                    } else {
                        foreach ($partsName as $partName) {
                            $partsNote[] = $partName;
                        }
                        $partsName = [];
                        $partsNote[] = $part;
                        continue;
                    }
                }
                $partsName[] = $part;
            } else {
                if (count($partsName) >= 3) {
                    $dto = new NameGenderDto(implode(' ', $partsName));
                    $this->fixNameAndGender($dto);
                    $informant = new InformantDto();
                    $informant->setNameAndGender($dto);
                } else {
                    foreach ($partsName as $partName) {
                        $partsNote[] = $partName;
                    }
                }
                $partsName = [];
                $partsNote[] = $part;
            }
        }

        if (count($partsName) >= 3) {
            $dto = new NameGenderDto(implode(' ', $partsName));
            $this->fixNameAndGender($dto);
            $informant = new InformantDto();
            $informant->setNameAndGender($dto);
        } else {
            foreach ($partsName as $partName) {
                $partsNote[] = $partName;
            }
        }
        if ($informant) {
            foreach ($partsNote as $key => $part) {
                if (str_contains($part, 'г.н.')) {
                    $informant->birth = (int) trim($part);
                    unset($partsNote[$key]);
                }
            }
            $notes = trim(implode(' ', $partsNote), " .;,\t\n\r\0\x0B");
            $notes = str_replace([' ,', ' .'], [',', '.'], $notes);
            $informant->notes = $notes;
        }

        return $informant;
    }

    /**
     * @param PersonBsuDto $dto
     * @return array<StudentDto>
     */
    public function detectStudents(PersonBsuDto $dto): array
    {
        $name = TextHelper::replaceLetters($dto->name);
        if (str_contains($name, 'група студэнтаў') || str_contains($name, 'группа студэнтаў')) {
            return [];
        }

        $name = str_replace(
            array('студэнты', 'студэнткі', 'студэнтка', 'студэнт', 'студ.', 'Студэнты', 'збіральнік' , '-'),
            array(''),
            $name
        );
        $name = trim($name);

        $name = $this->templateName($name);

        // Aaaa i Bbbb -> Aaaa Bbbb
        $name = str_replace([' і ',' и '], ' ', $name);

        // Type of parts
        $type = '';
        $parts = explode(' ', $name);
        foreach ($parts as $part) {
            if (mb_strlen($part) === 1) {
                $part .= '.';
            }
            if (str_contains($part, '.')) {
                $type .= 'S';
            } else {
                $type .= 'L';
            }
        }

        // Detect student's names
        $names = [];
        if (strlen($type) === 1) {
            $names[] = $name;
        }
        if ($type === 'LS' || $type === 'LL') {
            $names[] = implode(' ', $parts);
        }
        if ($type === 'LSL') {
            $part2last = mb_substr($parts[2], -3);
            if (in_array($part2last, ['віч', 'вна'])) {
                $names[] = $parts[0] . ' ' . $parts[1] . ' ' . $parts[2];
            } else {
                $names[] = $parts[0] . ' ' . $parts[1];
                $names[] = $parts[2];
            }
        }
        if ($type === 'LLS') {
            $names[] = $parts[0];
            $names[] = $parts[1] . ' ' . $parts[2];
        }
        if ($type === 'LLL') {
            $names[] = $parts[0] . ' ' . $parts[1] . ' ' . $parts[2];
        }
        if ($type === 'LSLS' || $type === 'LLLL') {
            $names[] = $parts[0] . ' ' . $parts[1];
            $names[] = $parts[2] . ' ' . $parts[3];
        }
        if ($type === 'LLLLLL') {
            $names[] = $parts[0] . ' ' . $parts[1] . ' ' . $parts[2];
            $names[] = $parts[3] . ' ' . $parts[4] . ' ' . $parts[5];
        }
        if ($type === 'LLSLS') {
            $names[] = $parts[0];
            $names[] = $parts[1] . ' ' . $parts[2];
            $names[] = $parts[3] . ' ' . $parts[4];
        }
        if ($type === 'LSLLS') {
            $names[] = $parts[0] . ' ' . $parts[1];
            $names[] = $parts[2];
            $names[] = $parts[3] . ' ' . $parts[4];
        }
        if ($type === 'LSLSL') {
            $names[] = $parts[0] . ' ' . $parts[1];
            $names[] = $parts[2] . ' ' . $parts[3];
            $names[] = $parts[4];
        }
        if ($type === 'LSLSLS') {
            $names[] = $parts[0] . ' ' . $parts[1];
            $names[] = $parts[2] . ' ' . $parts[3];
            $names[] = $parts[4] . ' ' . $parts[5];
        }
        if ($type === 'LSLSLSLS') {
            $names[] = $parts[0] . ' ' . $parts[1];
            $names[] = $parts[2] . ' ' . $parts[3];
            $names[] = $parts[4] . ' ' . $parts[5];
            $names[] = $parts[6] . ' ' . $parts[7];
        }

        if (empty($names)) {
            throw new \RuntimeException('Bad name of persons: ' . $dto->name);
        }

        $result = [];
        foreach ($names as $_name) {
            $student = new StudentDto();
            $student->name = trim($_name);
            $student->addLocation($dto->place);

            $result[] = $student;
        }

        return $result;
    }

    /**
     * @param InformantDto $dto
     * @param array<StudentDto> $students
     * @param string|null $fullInformantName
     * @return void
     */
    public function informantToStudent(InformantDto $dto, array &$students, ?string $fullInformantName = null): void
    {
        $isNew = true;
        $name = $fullInformantName ?? $dto->name;

        foreach ($students as $student) {
            if ($this->isSameNames($student->name, $name)) {
                $student->addLocations($dto->locations);
                $isNew = false;
                break;
            }
        }
        if ($isNew) {
            $student = new StudentDto();
            $student->name = $name;
            $student->addLocations($dto->locations);
            $students[] = $student;
        }
    }

    private function templateName(string $name): string
    {
        // a. -> A.
        $pos = mb_strpos($name, '.');
        while ($pos > 0 && $pos < mb_strlen($name) - 1) {
            $name = mb_substr($name, 0, $pos - 1) . mb_strtoupper(mb_substr($name, $pos - 1, 1)) . mb_substr($name, $pos);
            $pos = mb_strpos($name, '.', $pos + 1);
        }

        // A. A. -> A.A.
        $pos = mb_strpos($name, '. ');
        while ($pos > 0 && $pos < mb_strlen($name) - 3 && mb_substr($name, $pos + 3, 1) === '.') {
            $name = mb_substr($name, 0, $pos + 1) . mb_substr($name, $pos + 2);
            $pos = mb_strpos($name, '.', $pos + 2);
        }

        // A.A -> A.A.
        if (
            mb_substr($name, -1) !== '.'
            && (
                in_array(mb_substr($name, mb_strlen($name) - 2, 1), [' ', '.'], true)
                || in_array(mb_substr($name, mb_strlen($name) - 3, 1), [' ', '.'], true)
            )
        ) {
            $name .= '.';
        }

        return $name;
    }

    public function parseInformant(PersonBsuDto $personBsuDto): InformantDto
    {
        $informant = new InformantDto();
        $informant->name = $this->templateName($personBsuDto->name);
        $informant->birth = $personBsuDto->birth;
        $informant->place = $personBsuDto->place;
        $informant->geoPoint = $personBsuDto->geoPoint;
        $informant->addLocation($personBsuDto->place);

        return $informant;
    }

    public function isSameNames(string $nameA, string $nameB): bool
    {
        $nameA = str_replace(['.', '  '], ['. ', ' '], $nameA);
        $partsA = explode(' ', trim($nameA));
        $nameB = str_replace(['.', '  '], ['. ', ' '], $nameB);
        $partsB = explode(' ', trim($nameB));

        $amount = 0;
        foreach ($partsB as $keyB => $partB) {
            if (str_contains($partB, '.')) {
                continue;
            }
            $keyA = array_search($partB, $partsA);
            if ($keyA !== false) {
                unset($partsA[$keyA], $partsB[$keyB]);
                $amount++;
            }
        }
        if ($amount === 0) {
            return false;
        }
        if (empty($partsA) && empty($partsB)) {
            return ($amount > 1);
        }
        if ($amount > 1 && (empty($partsA) || empty($partsB))) {
            return true;
        }

        $partsA = array_values($partsA);
        $partsB = array_values($partsB);
        if (!isset($partsA[0], $partsB[0])) {
            return false;
        }

        $partsA2 = $partsA[1] ?? '';
        $partsB2 = $partsB[1] ?? '';
        $isOnlyLong1 = !str_contains($partsA[0], '.') && !str_contains($partsB[0], '.');
        $isOnlyLong2 = $partsA2 !== '' && !str_contains($partsA2, '.') && $partsB2 !== '' && !str_contains($partsB2, '.');
        $variantsA = $this->getNameVariants($partsA[0], $partsA2, $partsB2 !== '', $isOnlyLong1, $isOnlyLong2);
        $variantsB = $this->getNameVariants($partsB[0], $partsB2, $partsA2 !== '', $isOnlyLong1, $isOnlyLong2);
        foreach ($variantsA as $variantA) {
            if (in_array($variantA, $variantsB, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $namePart1
     * @param string $namePart2
     * @param bool $isOnlyBoth
     * @param bool $isOnlyLong1
     * @param bool $isOnlyLong2
     * @return array<string>
     */
    private function getNameVariants(
        string $namePart1,
        string $namePart2,
        bool $isOnlyBoth,
        bool $isOnlyLong1,
        bool $isOnlyLong2,
    ): array {
        $variants = [];

        $name1short = str_contains($namePart1, '.') ? $namePart1 : mb_substr($namePart1, 0, 1) . '.';
        $name1long = str_contains($namePart1, '.') ? null : mb_substr($namePart1, 0, 4); // compare by first 4 letters
        if (!$isOnlyBoth || empty($namePart2)) {
            if (!$isOnlyLong1) {
                $variants[] = $name1short;
            }
            if (null !== $name1long) {
                $variants[] = $name1long;
            }
        }

        if (!empty($namePart2)) {
            $name2short = str_contains($namePart2, '.') ? $namePart2 : mb_substr($namePart2, 0, 1) . '.';
            $name2long = str_contains($namePart2, '.') ? null : mb_substr($namePart2, 0, 4); // compare by first 4 letters
            if (!$isOnlyLong1 && !$isOnlyLong2) {
                $variants[] = $name1short . $name2short;
            }
            if (!$isOnlyLong1 && null !== $name2long) {
                $variants[] = $name1short . ' ' . $name2long;
            }

            if (null !== $name1long) {
                if (!$isOnlyLong2) {
                    $variants[] = $name1long . $name2short;
                }
                if (null !== $name2long) {
                    $variants[] = $name1long . ' ' . $name2long;
                }
            }
        }

        return $variants;
    }

    public function getFullName(string $nameA, string $nameB): string
    {
        $nameA = str_replace(['.', '  '], ['. ', ' '], $nameA);
        $partsA = explode(' ', trim($nameA));
        $nameB = str_replace(['.', '  '], ['. ', ' '], $nameB);
        $partsB = explode(' ', trim($nameB));

        // Exchange
        if (count($partsB) < count($partsA)) {
            $partsC = $partsA;
            $partsA = $partsB;
            $partsB = $partsC;
        }

        $keysSkip = [];
        foreach ($partsB as $keyB => $partB) {
            if (str_contains($partB, '.')) {
                continue;
            }
            $keyA = array_search($partB, $partsA);
            if ($keyA !== false) {
                unset($partsA[$keyA]);
                $keysSkip[] = $keyB;
            }
        }

        $partsA = array_values($partsA);
        $keyA = 0;
        $names = [];
        foreach ($partsB as $keyB => $partB) {
            if (in_array($keyB, $keysSkip, true)) {
                $names[] = $partB;
            } else {
                $name = $this->getLongTextForFullName($partB, $partsA[$keyA] ?? '');
                $names[] = $name;
                $keyA++;
            }
        }

        $result = preg_replace('!\s+!', ' ', implode(' ', $names));
        return $this->templateName($result);
    }

    private function getLongTextForFullName(string $name1, string $name2): string
    {
        $longText = mb_strlen($name1) > mb_strlen($name2) ? $name1 : $name2;

        return ($longText !== '' ? ' ' : '') . $longText;
    }

    /**
     * @param string $content
     * @param string $additionalNotes
     * @return array<InformantDto>
     */
    public function getInformants(string $content, string $additionalNotes = ''): array
    {
        $informants = [];
        $hasSemicolon = str_contains($content, ';');

        $char = $hasSemicolon ? ';' : ',';
        // A, b  +  C, d => A, b, C, d
        if (str_contains($content, ' + ')) {
            $content = str_replace(' + ', $char, $content);
        }

        // A, b  і  C, d => A, b, C, d
        if (str_contains($content, ' і ')) {
            $content = str_replace(' і ', $char, $content);
        }

        $persons = [];
        $partsBase = explode(';', $content);
        foreach ($partsBase as $partBase) {
            // A, b, c, A, c (text K), A, c   as   A, b, c; A, c (text K); A, c
            $parts = explode(',', $partBase);
            $name = '';
            foreach ($parts as $part) {
                [$text, $notes] = $this->textHelper->getNotes($part);
                $isNotLocation = !str_contains($part, 'в.') && !str_contains($part, 'р-н') && !str_contains($part, 'раён');
                if ($isNotLocation && ($this->isPersonName($text) || null !== $this->getPersonByFullName($text))) {
                    if ($name !== '') {
                        $persons[] = $name;
                    }
                    $name = $part;
                } else {
                    $name .= (empty($name) ? '' : ',') . $part;
                }
            }
            if ($name !== '') {
                $persons[] = $name;
            }
        }

        foreach ($persons as $text) {
            $text = trim($text);
            if (empty($text)) {
                continue;
            }

            $infNotes = [];
            if (!empty($additionalNotes)) {
                $infNotes[] = $additionalNotes;
            }

            $pos = mb_strpos($text, ',');
            if ($pos !== false) {
                $name = trim(mb_substr($text, 0, $pos));
                $text = mb_substr($text, $pos + 1);
            } else {
                [$name, $text] = $this->textHelper->getNotes($text);
                //$name = trim($text);
                //$text = '';
            }
            $informant = $pos !== false ? $this->getPersonByFullName($name) : null;
            if (null === $informant) {
                $name = trim($name, " ,;\t\n\r\0\x0B");
                $len = mb_strlen($name);
                if ($len > 2 && mb_substr($name, -1) === '.' && !$this->textHelper->isName(mb_substr($name, $len - 2, 1))) {
                    $name = mb_substr($name, 0, -1);
                }
                $informant = new InformantDto();
                $informant->name = $name;
            }

            [$text, $notes] = $this->textHelper->getNotes($text);
            if ($text === '' && $notes !== '') {
                $text = $notes;
                $notes = '';
            }
            if (!empty($notes)) {
                $infNotes[] = $notes;
            }

            $key = 0;
            $parts = explode(',', $text);
            if (isset($parts[$key])) {
                $birth = trim(str_replace(['г.н.', 'г.н'], '', $parts[$key]), " .,;\t\n\r\0\x0B");
                try {
                    $date = Carbon::createFromFormat('d.m.Y', $birth);
                    if ($date instanceof Carbon) {
                        $informant->birthDay = $date;
                        $informant->birth = $date->year;
                        $key++;
                    }
                } catch (\Exception $e) {
                }
                if (!$informant->birth && is_numeric($birth)) {
                    $birth = (int) $birth;
                    if ($birth < 1900 || $birth > 2020) {
                        if ($birth !== 0) { // if = 0 then this text is location
                            $infNotes[] = $parts[$key];
                            $key++;
                        }
                    } else {
                        $informant->birth = $birth;
                        $key++;
                    }
                }
            }
            if (isset($parts[$key])) {
                $location = str_replace('з ', '', trim($parts[$key], " .\t\n\r\0\x0B"));
                if ('' !== $location) {
                    $informant->addLocation($location);
                }
                $key++;
            }

            while (isset($parts[$key])) {
                $note = trim($parts[$key]);
                $note = preg_replace('!\s+!', ' ', $note);
                $infNotes[] = $note;
                $key++;
            }
            if (!empty($informant->notes)) {
                array_unshift($infNotes, $informant->notes);
            }
            $informant->notes = implode(', ', $infNotes);

            $dto = $informant->getNameAndGender();
            $this->fixNameAndGender($dto);
            $informant->name = $dto->getName();
            $informant->gender = $dto->gender;

            $this->addInformants($informants, $informant);
        }

        return $informants;
    }

    public function normalizeName(string $name): string
    {
        $name = TextHelper::replaceLetters($name);
        $name = str_replace(
            ['и', 'ау', 'еу', 'іу', 'ыу', 'оу', 'эу', 'ёу', 'ді', 'де', 'дя', 'дё', 'щ', 'И'
                , 'овн', 'авн', 'евн', 'эвн', 'івн', 'ёвн', 'ывн', 'ті', 'те', 'тя', 'тё', '`', '’',
                'овс', 'авс', 'евс', 'эвс', 'івс', 'ёвс', 'ывс'],
            ['і', 'аў', 'еў', 'іў', 'ыў', 'оў', 'эў', 'ёў', 'дзі', 'дзе', 'дзя', 'дзё', 'шч', 'І'
                , 'оўн', 'аўн', 'еўн', 'эўн', 'іўн', 'ёўн', 'ыўн', 'ці', 'це', 'ця', 'цё', '\'', '\'',
                'оўс', 'аўс', 'еўс', 'эўс', 'іўс', 'ёўс', 'ыўс'],
            $name
        );

        return trim($name);
    }

    /**
     * @param NameGenderDto $dto
     * @return array<string> All middle names of person
     */
    public function fixNameAndGender(NameGenderDto $dto): array
    {
        $detectedGenders = [];
        $detectedTypes = []; // Normal: 0 0 1 1 1 2 2 3 3 3 4 4 0 0 1 1 2 3 0 0 0
        $detectedNames = [];
        $detectedFullNames = [];

        $name = TextHelper::cleanManySpaces($dto->getName());
        $name = str_replace(' (', '(', $name);
        $parts = $this->textHelper->explodeWithBrackets([' '], $name);
        foreach ($parts as $key => $part) {
            $isMayByLastName = str_contains($part, '<');
            if (!$isMayByLastName) {
                $part = trim(str_replace('(', ' (', $this->normalizeName($part)));
            }

            $isBadName = $part !== str_replace(['[', ']', '..'], '', $part);
            if ($isBadName) {
                $detectedNames[$key] = $part;
                $detectedFullNames[$key] = $detectedNames[$key];
                $detectedGenders[$key] = GenderType::UNKNOWN;
                $detectedTypes[$key] = self::NUM_LAST;
                continue;
            }

            // Hide notes from text
            [$onlyName, $notes] = !$isMayByLastName
                ? $this->textHelper->getNotes($part)
                : [$part, ''];

            // A.A. as two names
            $names = explode(' ', trim(str_replace(['.', '(', ')'], ['. ', '', ''], $onlyName)));
            $isName = true;
            foreach ($names as $name) {
                if (!$this->textHelper->isNameWithBrackets($name)) {
                    $isName = false;
                    break;
                }
            }
            if (!$isName) {
                $detectedNames[$key] = $part;
                $detectedFullNames[$key] = $detectedNames[$key];
                $detectedGenders[$key] = GenderType::UNKNOWN;
                $detectedTypes[$key] = $isMayByLastName ? self::NUM_LAST : self::NUM_UNDEFINED;
                continue;
            }

            $withBrackets = $this->textHelper->isNameWithBrackets($onlyName) !== $this->textHelper->isName($onlyName);
            $onlyName = str_replace(['(', ')'], '', $onlyName);

            foreach (GenderType::REPLACE_NAMES as $name => $correctName) {
                if ($name === $onlyName) {
                    $onlyName = $correctName;
                }
            }

            $right3 = mb_substr($onlyName, -3);
            $right4 = mb_substr($onlyName, -4);
            if (GenderType::isMaleMiddle($onlyName)) {
                $detectedGenders[$key] = GenderType::MALE;
                $detectedTypes[$key] = self::NUM_MIDDLE;
            } elseif (($g = GenderType::getGender($onlyName)) !== GenderType::UNKNOWN) {
                $detectedGenders[$key] = $g;
                $detectedTypes[$key] = self::NUM_FIRST;
            } elseif (in_array($right3, GenderType::MIDDLE_3_LAST_FEMALE)) {
                $detectedGenders[$key] = GenderType::FEMALE;
                $detectedTypes[$key] = self::NUM_MIDDLE;
            } elseif (in_array($right3, GenderType::LAST_3_LAST_FEMALE)) {
                $detectedGenders[$key] = GenderType::FEMALE;
                $detectedTypes[$key] = self::NUM_LAST;
            } elseif (in_array($right3, GenderType::LAST_3_LAST_MALE)) {
                $detectedGenders[$key] = GenderType::MALE;
                $detectedTypes[$key] = self::NUM_LAST;
            } elseif (in_array($right4, GenderType::LAST_4_LAST_FEMALE)) {
                $detectedGenders[$key] = GenderType::FEMALE;
                $detectedTypes[$key] = self::NUM_LAST;
            } elseif (in_array($onlyName, GenderType::ANY_OTHERS, true)) {
                $detectedGenders[$key] = GenderType::UNKNOWN;
                $detectedTypes[$key] = self::NUM_FIRST;
            } elseif (mb_substr($onlyName, 1, 1) === '.') {
                $detectedGenders[$key] = GenderType::UNKNOWN;
                $detectedTypes[$key] = self::NUM_FIRST_MIDDLE;
            } else {
                $detectedGenders[$key] = GenderType::UNKNOWN;
                $detectedTypes[$key] = self::NUM_LAST;
            }
            $detectedNames[$key] = $onlyName;
            $fullName = ($withBrackets ? '(' : '') . $onlyName . ($withBrackets ? ')' : '');
            if (!empty($notes)) {
                $fullName .= ' (' . $notes . ')';
            }
            $detectedFullNames[$key] = $fullName;

            // First name before middle name
            if ($key > 0 && $detectedTypes[$key] === self::NUM_MIDDLE && $detectedTypes[$key - 1] === self::NUM_LAST) {
                $detectedTypes[$key - 1] = self::NUM_FIRST;
            }
        }

        // Case: last name as secondary first name (ex: Юрый Ягорка)
        $counts = array_count_values($detectedTypes);
        if (isset($counts[self::NUM_FIRST]) && $counts[self::NUM_FIRST] >= 2) {
            $baseKey = null;
            $notBaseKeys = [];
            $skip = false;
            foreach ($detectedTypes as $key => $type) {
                if ($type !== self::NUM_FIRST) {
                    continue;
                }

                if (!GenderType::isBaseName($detectedNames[$key])) {
                    $notBaseKeys[] = $key;
                    continue;
                }

                if ($baseKey !== null) {
                    $skip = true;
                    break;
                }

                $baseKey = $key;
            }
            if (!$skip && null !== $baseKey && !empty($notBaseKeys)) {
                // Other first names to last names
                foreach ($notBaseKeys as $notBaseKey) {
                    $detectedTypes[$notBaseKey] = self::NUM_LAST;
                    $detectedGenders[$notBaseKey] = GenderType::UNKNOWN;
                }
            }
        }

        // Case middle name as the second last name (ex: Міхайлавіч Берташ), '...віч' as middle
        $counts = array_count_values($detectedTypes);
        if (($counts[self::NUM_LAST] ?? 0) >= 2) {
            $baseKey = null;
            $notBaseKeys = [];
            $skip = false;
            foreach ($detectedTypes as $key => $type) {
                if ($type !== self::NUM_LAST) {
                    continue;
                }

                if (!GenderType::isMaybeMaleMiddle($detectedNames[$key])) {
                    if (mb_substr($detectedNames[$key], 1, 1) !== '.') { // (ex: Міхайлавіч Б.), '...віч' as last
                        $notBaseKeys[] = $key;
                    }
                    continue;
                }

                if ($baseKey !== null) {
                    $skip = true;
                    break;
                }

                $baseKey = $key;
            }
            if (!$skip && null !== $baseKey && !empty($notBaseKeys)) {
                // Last name to middle name
                $detectedTypes[$baseKey] = self::NUM_MIDDLE;
                $detectedGenders[$baseKey] = GenderType::MALE;
            }
        }

        // Case only 1 notBase first name with short other names (ex: Кірык В.У.)
        $counts = array_count_values($detectedTypes);
        if (
            ($counts[self::NUM_FIRST_MIDDLE] ?? 0) > 0
            && ($counts[self::NUM_FIRST] ?? 0) === 1
            && (($counts[self::NUM_LAST] ?? 0) + ($counts[self::NUM_MIDDLE] ?? 0)) === 0
        ) {
            $key = array_search(self::NUM_FIRST, $detectedTypes);
            $detectedTypes[$key] = self::NUM_LAST;
            $detectedGenders[$key] = GenderType::UNKNOWN;
        }

        // Case only 1 notBase first name as Unknown
        if (count($detectedTypes) === 1 && $detectedTypes[0] === self::NUM_FIRST && !GenderType::isBaseName($detectedNames[0])) {
            $detectedTypes[0] = self::NUM_UNDEFINED;
            $detectedGenders[0] = GenderType::UNKNOWN;
        }

        // Case of last name as middle with short other names (ex: Савіч М.І.)
        $counts = array_count_values($detectedTypes);
        if (
            ($counts[self::NUM_FIRST_MIDDLE] ?? 0) > 0
            && ($counts[self::NUM_LAST] ?? 0) === 0
            && ($counts[self::NUM_MIDDLE] ?? 0) === 1
        ) {
            $key = array_search(self::NUM_MIDDLE, $detectedTypes);
            $detectedTypes[$key] = self::NUM_LAST;
            $detectedGenders[$key] = GenderType::UNKNOWN;
        }

        // Case of last name as middle with long names (ex: Трафімаўна Марына Рыгораўна)
        $counts = array_count_values($detectedTypes);
        if (
            ($counts[self::NUM_LAST] ?? 0) === 0
            && ($counts[self::NUM_MIDDLE] ?? 0) > 1
        ) {
            $key = array_search(self::NUM_MIDDLE, $detectedTypes);
            $detectedTypes[$key] = self::NUM_LAST;
            $detectedGenders[$key] = GenderType::UNKNOWN;
        }

        // Case of different genders without last name (ex: Васілевіч Ірына)
        $counts = array_count_values($detectedGenders);
        if (
            ($counts[GenderType::MALE] ?? 0) > 0
            && ($counts[GenderType::FEMALE] ?? 0) > 0
            && !in_array(self::NUM_LAST, $detectedTypes, true)
        ) {
            foreach ($detectedTypes as $key => $type) {
                if (
                    $type === self::NUM_MIDDLE
                    && $detectedGenders[$key] === GenderType::MALE
                    && GenderType::isMaleMiddle($detectedNames[$key])
                ) {
                    $detectedTypes[$key] = self::NUM_LAST;
                }
            }
        }

        // Detect gender
        $counts = array_count_values($detectedGenders);
        $amountMale = $counts[GenderType::MALE] ?? 0;
        $amountFemale = $counts[GenderType::FEMALE] ?? 0;
        if ($amountMale !== $amountFemale && max($amountMale, $amountFemale) > 0) {
            $gender = $amountMale < $amountFemale ? GenderType::FEMALE : GenderType::MALE;
            if ($dto->gender === GenderType::UNKNOWN) {
                $dto->gender = $gender;
            }
        }

        // Case for female with first and last names as last (ex: Івандзілава Вольга Кулрыёнава)
        if ($dto->gender === GenderType::FEMALE) {
            $keyLast = null;
            foreach ($detectedTypes as $key => $type) {
                if ($type === self::NUM_LAST) {
                    if (null === $keyLast) {
                        $keyLast = $key;
                    } else {
                        $detectedTypes[$key] = self::NUM_MIDDLE;
                    }
                }
            }
        }

        if ($dto->gender === GenderType::MALE && count($detectedTypes) === 3) {
            if (
                $detectedTypes === [self::NUM_FIRST, self::NUM_LAST, self::NUM_LAST]
                || $detectedTypes === [self::NUM_FIRST, self::NUM_LAST, self::NUM_LAST, self::NUM_LAST]
            ) {
                $detectedTypes[1] = self::NUM_MIDDLE; // first, middle, last
            }
            if ($detectedTypes === [self::NUM_LAST, self::NUM_FIRST, self::NUM_LAST]) {
                $detectedTypes[2] = self::NUM_MIDDLE; // last, first, middle
            }
        }

        $middleNames = [];
        $detectedTypes[] = 0; // add last empty block
        $resultParts = [];
        $nameParts = [];
        foreach ($detectedTypes as $key => $type) {
            if ($type === self::NUM_MIDDLE) {
                $middleNames[] = $detectedNames[$key] ?? '';
            }

            $name = $detectedFullNames[$key] ?? '';
            if ($type === self::NUM_UNDEFINED) {
                ksort($nameParts);
                foreach ($nameParts as $names) {
                    foreach ($names as $namePart) {
                        $resultParts[] = $namePart;
                    }
                }
                $nameParts = [];
                $resultParts[] = $name;
            } else {
                $nameParts[$type][] = $name;
            }
        }

        $dto->setName(trim(implode(' ', $resultParts)));

        return $middleNames;
    }
}
