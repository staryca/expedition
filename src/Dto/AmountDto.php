<?php

declare(strict_types=1);

namespace App\Dto;

class AmountDto
{
    private int $id;
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

    public function updateId(int $id): void
    {
        $this->id = $id;
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

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }
}
