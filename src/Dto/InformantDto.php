<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Additional\Musician;
use App\Entity\Type\GenderType;
use App\Parser\Columns\KoboInformantColumns;
use Carbon\Carbon;

class InformantDto extends StudentDto
{
    public ?int $birth = null;
    public ?Carbon $birthDay = null;
    public array $codeReports = [];
    public ?string $notes = null;
    public int $gender = GenderType::UNKNOWN;
    public ?string $confession = null;
    public ?Carbon $dateAdded = null;
    public ?string $photo = null;
    public ?string $photoUrl = null;
    public ?PlaceDto $birthPlace = null;
    public ?bool $isMusician = null;

    public function isSameBirth(InformantDto $informantDto): ?bool
    {
        if (null === $this->birth || null === $informantDto->birth) {
            return null;
        }

        return $this->birth === $informantDto->birth;
    }

    public function addCodeReport(string $codeReport): void
    {
        if (!in_array($codeReport, $this->codeReports, true)) {
            $this->codeReports[] = $codeReport;
        }
    }

    public function addCodeReports(array $codeReports): void
    {
        foreach ($codeReports as $codeReport) {
            $this->addCodeReport($codeReport);
        }
    }

    public function mergeInformant(InformantDto $informantDto): void
    {
        $this->addCodeReports($informantDto->codeReports);
        $this->addLocations($informantDto->locations);
        if ($informantDto->birth > 0) {
            $this->birth = $informantDto->birth;
        }
        if ($informantDto->birthDay && !$this->birthDay) {
            $this->birthDay = $informantDto->birthDay;
        }
        if (!empty($informantDto->notes) && !str_contains($this->notes, $informantDto->notes)) {
            $this->addNotes($informantDto->notes);
        }
        if (GenderType::UNKNOWN !== $informantDto->gender && $this->gender === GenderType::UNKNOWN) {
            $this->gender = $informantDto->gender;
        }
        if (!empty($informantDto->confession) && empty($this->confession)) {
            $this->confession = $informantDto->confession;
        }
        if ($informantDto->dateAdded && !$this->dateAdded) {
            $this->dateAdded = $informantDto->dateAdded;
        }
        if (!empty($informantDto->photo) && empty($this->photo)) {
            $this->photo = $informantDto->photo;
        }
        if (!empty($informantDto->photoUrl) && empty($this->photoUrl)) {
            $this->photoUrl = $informantDto->photoUrl;
        }
        if (null !== $informantDto->birthPlace && !$this->birthPlace) {
            $this->birthPlace = $informantDto->birthPlace;
        }
        if (null !== $informantDto->isMusician && null === $this->isMusician) {
            $this->isMusician = $informantDto->isMusician;
        }
    }

    public function addNotes(string $notes): void
    {
        $this->notes .= (empty($this->notes) ? '' : '. ') . $notes;
    }

    public static function fromKobo(array $data): self
    {
        $dto = new self();
        $dto->birth = $data[KoboInformantColumns::BIRTH_YEAR] ? (int) $data[KoboInformantColumns::BIRTH_YEAR] : null;
        $dto->notes = $data[KoboInformantColumns::COMMENTS] ?? null;
        if ($data[KoboInformantColumns::BIRTH_PLACE] === 'няма ў спісе, дадаць населены пункт') {
            $dto->addLocation($data[KoboInformantColumns::BIRTH_PLACE_ADDITIONAL]);
        } else {
            $dto->addLocation($data[KoboInformantColumns::BIRTH_PLACE]);
        }
        $dto->addCodeReport((string) $data[KoboInformantColumns::INDEX_REPORT]);
        $dto->name = $data[KoboInformantColumns::NAME] ?? null;
        $gender = $data[KoboInformantColumns::SEX] ?? null;
        $dto->gender = GenderType::getType($gender);
        if ($data[KoboInformantColumns::CONFESSION] === 'іншае') {
            $dto->confession = $data[KoboInformantColumns::CONFESSION_OTHER] ?? null;
        } elseif ($data[KoboInformantColumns::CONFESSION] !== 'не ўказана') {
            $dto->confession = $data[KoboInformantColumns::CONFESSION] ?? null;
        }
        $dto->dateAdded = $data[KoboInformantColumns::DATE_ADDED]
            ? Carbon::parse($data[KoboInformantColumns::DATE_ADDED])
            : null;
        $dto->photo = $data[KoboInformantColumns::PHOTO] ?? null;
        $dto->photoUrl = $data[KoboInformantColumns::PHOTO_URL] ?? null;

        return $dto;
    }

    public function getNameAndGender(): NameGenderDto
    {
        return new NameGenderDto($this->name, $this->gender);
    }

    public function setNameAndGender(NameGenderDto $nameAndGender): void
    {
        $this->name = $nameAndGender->getName();
        $this->gender = $nameAndGender->gender;
    }

    public function getHash(): string
    {
        return md5(
            $this->birth . '$1#'
            . $this->name . '$2#'
            . $this->gender . '$3#'
            . $this->getPlaceHash() . '$4#'
        );
    }

    public function detectMusician(): void
    {
        $this->isMusician = (new Musician())->isMusician($this->notes);
    }
}
