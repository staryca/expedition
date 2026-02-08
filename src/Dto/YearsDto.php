<?php

declare(strict_types=1);

namespace App\Dto;

class YearsDto
{
    public const int DIED_IS_UNKNOWN = -1;

    public ?int $birth = null;
    // Died = -1 if year is unknown
    public ?int $died = null;

    public function __construct(?int $birth = null, ?int $died = null)
    {
        $this->birth = $birth;
        $this->died = $died;
    }

    public function diedIsUnknown(): void
    {
        $this->died = self::DIED_IS_UNKNOWN;
    }
}
