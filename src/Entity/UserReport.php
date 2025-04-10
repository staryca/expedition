<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Type\UserRoleType;
use App\Repository\UserReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserReportRepository::class)]
#[ApiResource]
class UserReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userReports')]
    #[ORM\JoinColumn(nullable: false)]
    private User $participant;

    #[ORM\ManyToOne(inversedBy: 'userReports')]
    #[ORM\JoinColumn(nullable: false)]
    private Report $report;

    #[ORM\Column(length: 20)]
    private string $role = '';

    public function __construct(Report $report, User $participant)
    {
        $this->report = $report;
        $this->participant = $participant;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParticipant(): User
    {
        return $this->participant;
    }

    public function getReport(): Report
    {
        return $this->report;
    }

    public function setReport(?Report $report): static
    {
        $this->report = $report;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getRoleName(): string
    {
        return UserRoleType::ROLES[$this->role] ?? '[ Роль ?]';
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function isLeader(): ?bool
    {
        return $this->role === UserRoleType::ROLE_LEADER;
    }
}
