<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ApiResource]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150, unique: true)]
    private ?string $name = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private bool $isBase = false;

    /**
     * @var Collection<int, ReportBlock>
     */
    #[ORM\ManyToMany(targetEntity: ReportBlock::class, mappedBy: 'tags')]
    private Collection $reportBlocks;

    public function __construct()
    {
        $this->reportBlocks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function isBase(): bool
    {
        return $this->isBase;
    }

    public function setBase(bool $isBase): static
    {
        $this->isBase = $isBase;

        return $this;
    }

    /**
     * @return Collection<int, ReportBlock>
     */
    public function getReportBlocks(): Collection
    {
        return $this->reportBlocks;
    }

    public function addReportBlock(ReportBlock $reportBlock): static
    {
        if (!$this->reportBlocks->contains($reportBlock)) {
            $this->reportBlocks->add($reportBlock);
            $reportBlock->addTag($this);
        }

        return $this;
    }

    public function removeReportBlock(ReportBlock $reportBlock): static
    {
        if ($this->reportBlocks->removeElement($reportBlock)) {
            $reportBlock->removeTag($this);
        }

        return $this;
    }
}
