<?php

declare(strict_types=1);

namespace App\Dto;

class FolderInfo
{
    public string $name;
    public int $type;
    public ?int $reportId = null;
}
