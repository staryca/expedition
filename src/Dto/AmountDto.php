<?php

declare(strict_types=1);

namespace App\Dto;

class AmountDto
{
    private readonly int $id;
    private readonly string $name;
    private int $amount = 0;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): int
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
