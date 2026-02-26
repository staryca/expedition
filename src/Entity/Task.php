<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Type\TaskStatus;
use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ApiResource]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column]
    private int $status = TaskStatus::NEW;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?Report $report = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?ReportBlock $reportBlock = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?Informant $informant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusIcon(): ?string
    {
        return TaskStatus::getIcon($this->status);
    }

    public function getStatusText(): ?string
    {
        return TaskStatus::STATUSES[$this->status] ?? null;
    }

    public function getReport(): ?Report
    {
        return $this->report;
    }

    public function setReport(?Report $report): static
    {
        $this->report = $report;

        return $this;
    }

    public function getInformant(): ?Informant
    {
        return $this->informant;
    }

    public function setInformant(?Informant $informant): static
    {
        $this->informant = $informant;

        return $this;
    }

    public function getReportBlock(): ?ReportBlock
    {
        return $this->reportBlock;
    }

    public function setReportBlock(?ReportBlock $reportBlock): static
    {
        $this->reportBlock = $reportBlock;

        return $this;
    }

    public function getFollowReport(): ?Report
    {
        return $this->report ?? $this->reportBlock->getReport();
    }

    public function __toString(): string
    {
        return trim('#' . $this->id . ' ' . $this->getContent());
    }
}
