<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RitualRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RitualRepository::class)]
class Ritual
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'rituals')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $rituals;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $playlist = null;

    public function __construct(string $name)
    {
        $this->rituals = new ArrayCollection();
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getRitualList(): array
    {
        $list = [];
        $item = $this;

        while ($item) {
            array_unshift($list, $item);
            $item = $item->parent;
        }

        return $list;
    }

    public function getPlaylist(): ?string
    {
        return $this->playlist;
    }

    public function setPlaylist(?string $playlist): static
    {
        $this->playlist = $playlist;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
