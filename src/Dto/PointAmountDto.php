<?php

declare(strict_types=1);

namespace App\Dto;

class PointAmountDto
{
    private readonly string $id;
    private readonly string $name;
    private int $amount = 0;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function increaseAmount(): void
    {
        $this->amount++;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }
}
