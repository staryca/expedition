<?php

declare(strict_types=1);

namespace App\Dto;

class SubjectDto
{
    public string $name;
    public ?int $type = null;

    /** @var array<FileDto> $files */
    public array $files = [];

    public function hasFileMarkers(): bool
    {
        foreach ($this->files as $file) {
            if (!empty($file->markers)) {
                return true;
            }
        }

        return false;
    }
}
