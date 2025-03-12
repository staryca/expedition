<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Type\InformationType;
use App\Entity\Type\ReportBlockType;
use App\Parser\Columns\KoboReportColumns;

class ReportBlockDataDto
{
    public ?string $description = null;

    /** @var array<int, EpisodeDto> $episodes */
    public array $episodes = [];

    /** @var array<int> $informantKeys */
    public array $informantKeys = [];

    /** @var array<InformantDto> $informants */
    public array $informants = [];

    public ?OrganizationDto $organization = null;

    public ?int $organizationKey = null;

    /** @var array<string> $tags */
    public array $tags = [];

    public array $files = [];

    public array $additional = [];

    public int $type = ReportBlockType::TYPE_UNDEFINED;

    public ?string $videoNotes = null;

    public ?string $photoNotes = null;

    public ?string $userNotes = null;

    public function merge(ReportBlockDataDto $dto): void
    {
        if (null !== $dto->description) {
            $this->description = $dto->description;
        }
        if (!empty($dto->informantKeys)) {
            $this->informantKeys = $dto->informantKeys;
        }
        if (null !== $dto->organizationKey) {
            $this->organizationKey = $dto->organizationKey;
        }
        if (!empty($dto->tags)) {
            $this->tags = $dto->tags;
        }
        if (!empty($dto->files)) {
            $this->files = $dto->files;
        }
        if (!empty($dto->additional)) {
            $this->additional = $dto->additional;
        }
        if (!empty($dto->videoNotes)) {
            $this->videoNotes = $dto->videoNotes;
        }
        if (!empty($dto->photoNotes)) {
            $this->photoNotes = $dto->photoNotes;
        }
        if (!empty($dto->userNotes)) {
            $this->userNotes = $dto->userNotes;
        }
    }

    public static function fromKobo(array $data): self
    {
        $dto = new self();

        $type = ReportBlockType::getType($data[KoboReportColumns::TYPE]);
        if ($type === ReportBlockType::TYPE_UNDEFINED) {
            $type = ReportBlockType::getType($data[KoboReportColumns::TYPE_OTHER]);
        }
        $dto->type = $type;

        $dto->description = $data[KoboReportColumns::COMMENTS] ?? null;

        $information = $data[KoboReportColumns::INFORMATION] ?? '';
        foreach (explode(' ', $information) as $info) {
            $type = InformationType::getType($info);
            if (null !== $type) {
                $dto->additional[$type] = "+";
            }
        }

        $dto->videoNotes = $data[KoboReportColumns::VIDEO_NOTES] ?? null;
        $dto->photoNotes = $data[KoboReportColumns::PHOTO_NOTES] ?? null;
        $dto->userNotes = $data[KoboReportColumns::PERSON_COMMENT] ?? null;

        return $dto;
    }

    public function addInformants(InformantDto ...$informants): void
    {
        foreach ($informants as $informant) {
            $this->informants[] = $informant;
        }
    }
}
