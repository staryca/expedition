<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\InformantDto;
use App\Dto\NameGenderDto;
use App\Dto\OrganizationDto;
use App\Dto\PersonBsuDto;
use App\Dto\StudentDto;
use App\Entity\Informant;
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
                    $informant = $this->getPersonByFullName($part);
                    if (null !== $informant) {
                        $informant->detectMusician();
                        $this->addInformants($dto->informants, $informant);
                        continue;
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
            [$person, $notes] = TextHelper::getNotes($text);
            $parts = explode(' ', trim($person));
            if (count($parts) === 2) {
                $nameGenderDto = new NameGenderDto($person);
                $this->fixNameAndGender($nameGenderDto);
                if ($nameGenderDto->gender !== GenderType::UNKNOWN) {
                    $informant = new InformantDto();
                    $informant->setNameAndGender($nameGenderDto);
                    $informant->addNotes($notes);
                    $informant->detectMusician();

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
            if (!TextHelper::isName($part)) {
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
     * @param int|null $yearReport
     * @param bool $hasOnlyTwoNames
     * @return InformantDto|null
     */
    public function getPersonByFullName(string $name, ?int $yearReport = null, bool $hasOnlyTwoNames = false): ?InformantDto
    {
        $name = self::templateNameWithoutUpping($name);
        $name = preg_replace('!\s+!', ' ', $name);
        $amount = $hasOnlyTwoNames ? 2 : 3;
        $parts = explode(' ', trim($name));
        if (count($parts) < $amount) {
            return null;
        }
        $birth = self::getBirthYearFromNotes($parts, $yearReport);

        $partsName = [];
        $partsNote = [];
        $informant = null;
        $hasShortNames = false;
        foreach ($parts as $part) {
            if ('ад' === $part || 'Ад' === $part || 'от' === $part || 'От' === $part) {
                continue;
            }

            $part = TextHelper::fixName($part);
            $isShortNames = TextHelper::isShortNames($part);
            $hasShortNames = $hasShortNames || $isShortNames;
            $isName = TextHelper::isNameWithBrackets($part) || $isShortNames;
            if (!$isName) {
                $partU = TextHelper::lettersToUpper($part);
                if (GenderType::getGender($partU) !== GenderType::UNKNOWN) {
                    $part = $partU;
                    $isName = true;
                }
            }
            if (!$informant && $isName) {
                $last = mb_substr($part, -1);
                $hasShortNames = $hasShortNames || (mb_strlen($part) === 2 && $last === '.');
                if (!$isShortNames && mb_strlen($part) > 2 && in_array($last, [',', ':', '.', ';', '-']) && !GenderType::isShortMiddle($part)) {
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
                if (count($partsName) >= $amount) {
                    $dto = new NameGenderDto(implode(' ', $partsName));
                    $this->fixNameAndGender($dto);
                    if (!$hasOnlyTwoNames || $dto->gender !== GenderType::UNKNOWN) {
                        $informant = new InformantDto();
                        $informant->setNameAndGender($dto);
                    }
                }
                if (!$informant) {
                    foreach ($partsName as $partName) {
                        $partsNote[] = $partName;
                    }
                }
                $partsName = [];
                $partsNote[] = $part;
            }
        }

        if (!$informant && count($partsName) >= $amount) {
            $dto = new NameGenderDto(implode(' ', $partsName));
            $this->fixNameAndGender($dto);
            if (!$hasOnlyTwoNames || $dto->gender !== GenderType::UNKNOWN || ($hasShortNames && empty($partsNote))) {
                $informant = new InformantDto();
                $informant->setNameAndGender($dto);
            }
        }
        if (!$informant && $birth && count($partsName) > 0 && count($partsName) <= 2) {
            $dto = new NameGenderDto(implode(' ', $partsName));
            $this->fixNameAndGender($dto);
            $informant = new InformantDto();
            $informant->setNameAndGender($dto);
        }
        if ($informant) {
            $parts = [];
            foreach ($partsNote as $part) {
                [$note, $name2] = TextHelper::getNames($part);
                if ($name2 !== '') {
                    $informant->name .= ' [' . $name2 . ']';
                }
                [$note1, $note2] = TextHelper::getNotes($note);
                if ($note1 !== '') {
                    $parts[] = $note1;
                }
                if ($note2 !== '') {
                    $parts[] = $note2;
                }
            }
            if ($birth) {
                $informant->birth = $birth;
            }
            if (!empty($parts)) {
                $notes = trim(implode(' ', $parts), " .;,\t\n\r\0\x0B");
                $notes = str_replace([' ,', ' .'], [',', '.'], $notes);
                $informant->notes = $notes;
            }
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
            $name = mb_substr($name, 0, $pos - 1)
                . mb_strtoupper(mb_substr($name, $pos - 1, 1))
                . mb_substr($name, $pos);
            $pos = mb_strpos($name, '.', $pos + 1);
        }

        return self::templateNameWithoutUpping($name);
    }

    private static function templateNameWithoutUpping(string $name): string
    {
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

        // 'A.A.text' => 'A.A. text'
        $pos = mb_strpos($name, '.');
        while ($pos > 0 && $pos < mb_strlen($name) - 4 && mb_substr($name, $pos + 2, 1) === '.') {
            $name = mb_substr($name, 0, $pos + 3) . ' ' . mb_substr($name, $pos + 3);
            $pos = mb_strpos($name, '.', $pos + 2);
        }

        return preg_replace('!\s+!', ' ', $name);
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
        $isOnlyLong2 =
            $partsA2 !== '' && !str_contains($partsA2, '.') && $partsB2 !== '' && !str_contains($partsB2, '.');
        $variantsA =
            $this->getNameVariants($partsA[0], $partsA2, $partsB2 !== '', $isOnlyLong1, $isOnlyLong2);
        $variantsB =
            $this->getNameVariants($partsB[0], $partsB2, $partsA2 !== '', $isOnlyLong1, $isOnlyLong2);
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
            // compare by first 4 letters
            $name2long = str_contains($namePart2, '.') ? null : mb_substr($namePart2, 0, 4);
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
     * @param null $isMusician
     * @param int|null $yearReport
     * @return array<InformantDto>
     */
    public function getInformants(string $content, string $additionalNotes = '', $isMusician = null, ?int $yearReport = null): array
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
            $parts = TextHelper::explodeWithBrackets([','], $partBase);
            $name = '';
            foreach ($parts as $part) {
                [$text, $notes] = TextHelper::getNotes($part);
                $isLocation = LocationService::isLocation($part);
                if (!$isLocation && ($this->isPersonName($text) || null !== $this->getPersonByFullName($text, $yearReport, true))) {
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

            $pos = mb_strpos($text, ',');
            if ($pos !== false) {
                $name = trim(mb_substr($text, 0, $pos));
                $text = mb_substr($text, $pos + 1);
            } else {
                [$name, $text] = TextHelper::getNotes($text);
                //$name = trim($text);
                //$text = '';
            }
            $informant = $this->getPersonByFullName($name, $yearReport, true);
            if (null === $informant) {
                $name = trim($name, " ,;\t\n\r\0\x0B");
                $len = mb_strlen($name);
                if (
                    $len > 2
                    && mb_substr($name, -1) === '.'
                    && !TextHelper::isName(mb_substr($name, $len - 2, 1))
                ) {
                    $name = mb_substr($name, 0, -1);
                }
                $informant = new InformantDto();
                $informant->name = $name;
            }

            $parts = TextHelper::explodeBySeparatorAndBrackets(',', $text);

            if (!empty($informant->notes)) {
                array_unshift($parts, $informant->notes);
                $informant->notes = null;
            }

            foreach ($parts as $key => $part) {
                $part = preg_replace('!\s+!', ' ', $part);
                $part = ltrim($part, " .\n\r\t\v\0");
                if (mb_substr($part, -2) === ' .') {
                    $part = substr($part, 0, -2);
                }
                $part = trim($part, " ;\t\n\r\0\x0B");
                if ($part === '') {
                    unset($parts[$key]);
                } else {
                    $parts[$key] = $part;
                }
            }

            $birth = null;
            $date = self::getBirthDayFromNotes($parts);
            if ($date) {
                $informant->birthDay = $date;
                $informant->birth = $date->year;
            } else {
                $birth = self::getBirthYearFromNotes($parts, $yearReport);
            }
            if (!$informant->birth && is_numeric($birth)) {
                $informant->birth = $birth;
            }
            $location = LocationService::getLocationFromNotes($parts);
            if ($location) {
                $informant->addLocation($location);
            }

            $infNotes = [];
            if (!empty($additionalNotes)) {
                $infNotes[] = $additionalNotes;
            }
            foreach ($parts as $part) {
                if (!empty($part)) {
                    $infNotes[] = $part;
                }
            }
            $informant->notes = implode(', ', $infNotes);

            if (null !== $isMusician) {
                $informant->isMusician = $isMusician;
            } else {
                $informant->detectMusician();
            }

            $dto = $informant->getNameAndGender();
            $this->fixNameAndGender($dto);
            $informant->name = $dto->getName();
            $informant->gender = $dto->gender;

            $this->addInformants($informants, $informant);
        }

        return $informants;
    }

    private static function getBirthYearFromNotes(array &$notes, ?int $yearReport = null): ?int
    {
        $borderLetters = ['', ' ', ',', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $ages = ['min' => 4, 'max' => 120];
        $years = ['min' => 1800, 'max' => 2020];

        foreach ($notes as $key => $note) {
            //  25г => 25г.
            $pos = -1;
            while (false !== $pos) {
                $pos = mb_strpos($note, 'г', $pos + 1);
                if (
                    $pos > 0
                    && in_array(mb_substr($note, $pos - 1, 1), $borderLetters, true)
                    && in_array(mb_substr($note, $pos + 1, 1), $borderLetters, true)
                ) {
                    $note = mb_substr($note, 0, $pos + 1) . '.' . mb_substr($note, $pos + 1);
                    $pos = false;
                }
            }

            if (
                str_contains($note, 'год')
                || str_contains($note, 'гадоў')
                || str_contains($note, 'гады')
                || str_contains($note, 'лет')
                || (str_contains($note, 'г.') && !str_contains($note, 'г.н'))
            ) {
                $age = (int) $note;
                if ($age > $years['min'] && $age < $years['max']) {
                    unset($notes[$key]);
                    if (isset($notes[$key + 1]) && $notes[$key + 1] === 'н.') {
                        unset($notes[$key + 1]);
                    }
                    return $age;
                }
                if ($age > $ages['min'] && $age < $ages['max']) {
                    if ($yearReport) {
                        unset($notes[$key]);
                        return $yearReport - $age;
                    }
                    continue;
                }
                if ($key === 0) {
                    continue;
                }
                $age = (int) $notes[$key - 1];
                if ($age > $years['min'] && $age < $years['max']) {
                    unset($notes[$key - 1], $notes[$key]);
                    if (isset($notes[$key + 1]) && $notes[$key + 1] === 'н.') {
                        unset($notes[$key + 1]);
                    }
                    return $age;
                }
                if ($age > $ages['min'] && $age < $ages['max'] && null !== $yearReport) {
                    if ($key >= 2 && $notes[$key - 2] === 'каля') {
                        unset($notes[$key - 2]);
                    }
                    unset($notes[$key - 1], $notes[$key]);
                    return $yearReport - $age;
                }
                if ($age > 0) {
                    $notes[$key - 1] .= ' ' . $note;
                    $notes[$key] = '';
                }
            }

            if ($key > 0 && str_contains($note, 'г.н')) {
                $year = (int) $note;
                if ($year > $years['min'] && $year < $years['max']) {
                    unset($notes[$key]);
                    return $year;
                }

                $year = (int) $notes[$key - 1];
                if ($year > $years['min'] && $year < $years['max']) {
                    unset($notes[$key - 1], $notes[$key]);
                    return $year;
                }
                if ($year > 0) {
                    $notes[$key - 1] .= ' ' . $note;
                    $notes[$key] = '';
                }
            }
        }

        foreach ($notes as $key => $note) {
            $year = (int) $note;
            if ($year > $years['min'] && $year < $years['max']) {
                unset($notes[$key]);
                return $year;
            }
        }

        return null;
    }

    private static function getBirthDayFromNotes(array &$notes): ?Carbon
    {
        foreach ($notes as $key => $note) {
            try {
                $date = Carbon::createFromFormat('d.m.Y', $note);
                if ($date instanceof Carbon) {
                    unset($notes[$key]);
                    return $date;
                }
            } catch (\Exception $e) {
            }
        }

        return null;
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
        $parts = TextHelper::explodeWithBrackets([' '], $name);
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
                ? TextHelper::getNotes($part)
                : [$part, ''];

            // A.A. as two names
            $names = explode(' ', trim(str_replace(['.', '(', ')'], ['. ', '', ''], $onlyName)));
            $isName = true;
            foreach ($names as $name) {
                if (!TextHelper::isNameWithBrackets($name)) {
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

            $withBrackets = TextHelper::isNameWithBrackets($onlyName) !== TextHelper::isMultiName($onlyName);
            $onlyName = str_replace(['(', ')'], '', $onlyName);

            foreach (GenderType::REPLACE_NAMES as $name => $correctName) {
                if ($name === $onlyName) {
                    $onlyName = $correctName;
                }
            }
            foreach (GenderType::REPLACE_RIGHT_PARTS as $correctedPart => $rightParts) {
                foreach ($rightParts as $rightPart) {
                    $len = mb_strlen($rightPart);
                    if (mb_substr($onlyName, -$len) === $rightPart) {
                        $onlyName = mb_substr($onlyName, 0, -$len) . $correctedPart;
                    }
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

        // Case: fix first name only after detect gender
        $counts = array_count_values($detectedTypes);
        $genderTemp = $this->detectGenderByCounts($detectedGenders);
        if (!isset($counts[self::NUM_FIRST_MIDDLE])) {
            if (!isset($counts[self::NUM_FIRST])) {
                foreach ($detectedFullNames as $key => $fullName) {
                    $fixedName = GenderType::fixNameByRoot($fullName, $genderTemp);
                    if ($fullName !== $fixedName) {
                        $detectedFullNames[$key] = $fixedName;
                        $detectedTypes[$key] = self::NUM_FIRST;
                        if ($genderTemp === GenderType::UNKNOWN) {
                            $detectedGenders[$key] = GenderType::getGender($fixedName);
                        }
                        break;
                    }
                }
            } else {
                foreach ($detectedFullNames as $key => $fullName) {
                    if ($detectedTypes[$key] === self::NUM_FIRST) {
                        $detectedFullNames[$key] = GenderType::fixNameByRoot($fullName, $genderTemp);
                    }
                }
            }
        }

        // Case: last name as secondary first name (ex: Юрый Ягорка)
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

        // Case middle name as the second last name (ex: Сацэвіч Аўгіня Цітава) for female
        $counts = array_count_values($detectedTypes);
        if (($counts[self::NUM_LAST] ?? 0) >= 2) {
            $genderTemp = $this->detectGenderByCounts($detectedGenders);
            $genderTemp = $genderTemp === GenderType::UNKNOWN ? $dto->gender : $genderTemp;
            if ($genderTemp === GenderType::FEMALE) {
                $baseKey = null;
                foreach ($detectedTypes as $key => $type) {
                    if ($type !== self::NUM_LAST) {
                        continue;
                    }

                    $baseKey = $key;
                }
                // Last name to middle name
                $detectedTypes[$baseKey] = self::NUM_MIDDLE;
                $detectedGenders[$baseKey] = GenderType::FEMALE;
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
        if (
            count($detectedTypes) === 1
            && $detectedTypes[0] === self::NUM_FIRST
            && !GenderType::isBaseName($detectedNames[0])
        ) {
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
        $gender = $this->detectGenderByCounts($detectedGenders);
        if ($gender !== GenderType::UNKNOWN && $dto->gender === GenderType::UNKNOWN) {
            $dto->gender = $gender;
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

    private function detectGenderByCounts(array $detectedGenders): int
    {
        $counts = array_count_values($detectedGenders);
        $amountMale = $counts[GenderType::MALE] ?? 0;
        $amountFemale = $counts[GenderType::FEMALE] ?? 0;
        if ($amountMale === $amountFemale || max($amountMale, $amountFemale) === 0) {
            return GenderType::UNKNOWN;
        }

        return $amountMale < $amountFemale ? GenderType::FEMALE : GenderType::MALE;
    }

    /**
     * @param array<Informant> $informants Sorted by firstname
     * @return array<array<Informant>> Array of arrays with pairs of informants
     */
    public function getDuplicates(array $informants): array
    {
        $result = [];

        for ($i = 0; $i < count($informants) - 1; $i++) {
            $informant1 = $informants[$i];
            $informant2 = $informants[$i + 1];

            if (
                $this->isSameNames($informant1->getFirstName(), $informant2->getFirstName())
                && $informant1->hasBirthOrCurrentPlace($informant2->getGeoPointBirth(), $informant2->getGeoPointCurrent())
            ) {
                $result[] = [$informant1, $informant2];
            }
        }

        return $result;
    }
}
