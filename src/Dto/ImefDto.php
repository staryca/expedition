<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Ritual;
use Carbon\Carbon;

class ImefDto extends PlaceDto
{
    public ?Carbon $date = null;
    public string $name = '';
    public ?int $category = null;

    /** @var array<string> $tags */
    public array $tags = [];
    public ?Ritual $ritual = null;
    /** @var array<UserDto> $users */
    public array $users = [];
    /** @var array<InformantDto> $informants */
    public array $informants = [];

    // for debug
    public string $content = '';
    public string $folder = '';
}
