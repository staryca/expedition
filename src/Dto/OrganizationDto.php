<?php

declare(strict_types=1);

namespace App\Dto;

use App\Parser\Columns\KoboOrganizationColumns;
use Carbon\Carbon;

class OrganizationDto extends PlaceDto
{
    public string $name;
    public array $codeReports = [];
    public ?string $informantText = null;
    public ?string $notes = null;
    public ?Carbon $dateAdded = null;
    /**
     * Only for parsing
     * @var array<InformantDto> $informants
     */
    public array $informants = [];
    /**
     * Will be saved by Keys
     * @var array<int> $informantKeys
     */
    public array $informantKeys = [];

    public function isSame(PersonBsuDto $dto): bool
    {
        return
            $dto->name === $this->name
            && $dto->place === $this->place
            && $dto->geoPoint?->getId() === $this->geoPoint?->getId();
    }

    public function make(PersonBsuDto $dto): self
    {
        $this->name = $dto->name;
        $this->geoPoint = $dto->geoPoint;
        $this->place = $dto->place;
        $this->codeReports[] = $dto->codeReport;

        return $this;
    }

    public function addCodeReport(string $codeReport): void
    {
        if (!in_array($codeReport, $this->codeReports, true)) {
            $this->codeReports[] = $codeReport;
        }
    }

    public static function fromKobo(array $data): self
    {
        $dto = new self();
        $dto->notes = $data[KoboOrganizationColumns::COMMENTS] ?? null;
        $dto->addCodeReport((string) $data[KoboOrganizationColumns::INDEX_REPORT]);
        $dto->name = $data[KoboOrganizationColumns::NAME];
        $dto->dateAdded = $data[KoboOrganizationColumns::DATE_ADDED]
            ? Carbon::parse($data[KoboOrganizationColumns::DATE_ADDED])
            : null;
        $dto->place = '';
        $dto->geoPoint = null;

        return $dto;
    }
}
