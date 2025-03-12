<?php

declare(strict_types=1);

namespace App\Dto;

class FileDto extends FilePathDto
{
    public const NOTES_PART = 'бок ';

    public ?int $type = null;

    public ?string $notes = null;

    /** @var array<FileMarkerDto> $markers */
    public array $markers = [];

    /** @var array<VideoItemDto> $videoItems */
    public array $videoItems = [];
}
