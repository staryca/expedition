<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PackRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PackRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class Pack
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    #[ORM\Column]
    private bool $is_group;

    #[ORM\Column]
    private bool $is_man;

    #[ORM\Column]
    private bool $is_woman;

    #[ORM\Column(length: 110, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 30)]
    private string $namePlural;

    public function __construct()
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIsGroup(): ?bool
    {
        return $this->is_group;
    }

    public function setIsGroup(bool $is_group): self
    {
        $this->is_group = $is_group;

        return $this;
    }

    public function getIsMan(): ?bool
    {
        return $this->is_man;
    }

    public function setIsMan(bool $is_man): self
    {
        $this->is_man = $is_man;

        return $this;
    }

    public function getIsWoman(): ?bool
    {
        return $this->is_woman;
    }

    public function setIsWoman(bool $is_woman): self
    {
        $this->is_woman = $is_woman;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getNamePlural(): string
    {
        return $this->namePlural;
    }

    public function setNamePlural(string $namePlural): self
    {
        $this->namePlural = $namePlural;

        return $this;
    }
}
