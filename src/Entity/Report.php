<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Dto\LatLonDto;
use App\Entity\Type\TaskStatus;
use App\Repository\ReportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ApiResource]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    #[ORM\JoinColumn(nullable: false)]
    private Expedition $expedition;

    #[ORM\ManyToOne]
    private ?GeoPoint $geoPoint = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $geoNotes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAction = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreated = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 6, nullable: true)]
    private ?string $lat = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 6, nullable: true)]
    private ?string $lon = null;

    /**
     * @var Collection<int, UserReport>
     */
    #[ORM\OneToMany(targetEntity: UserReport::class, mappedBy: 'report', orphanRemoval: true)]
    private Collection $userReports;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, ReportBlock>
     */
    #[ORM\OneToMany(targetEntity: ReportBlock::class, mappedBy: 'report', orphanRemoval: true)]
    private Collection $blocks;

    #[ORM\Column(nullable: true)]
    private ?array $temp = null;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'report')]
    private Collection $tasks;

    public function __construct(Expedition $expedition)
    {
        $this->expedition = $expedition;
        $this->userReports = new ArrayCollection();
        $this->blocks = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): static
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateAction(): ?\DateTimeInterface
    {
        return $this->dateAction;
    }

    public function getTextDateAction(): string
    {
        return !$this->dateAction ? '' :
            ($this->dateAction->format('d.m') === '01.01'
                ? $this->dateAction->format('Y г.')
                : $this->dateAction->format('d.m.Y')
            );
    }

    public function getDateActionYear(): ?int
    {
        return !$this->dateAction ? null : (int)$this->dateAction->format('Y');
    }

    public function setDateAction(\DateTimeInterface $dateAction): static
    {
        $this->dateAction = $dateAction;

        return $this;
    }

    public function getExpedition(): Expedition
    {
        return $this->expedition;
    }

    public function setExpedition(Expedition $expedition): static
    {
        $this->expedition = $expedition;

        return $this;
    }

    public function getGeoPoint(): ?GeoPoint
    {
        return $this->geoPoint;
    }

    public function setGeoPoint(?GeoPoint $geoPoint): static
    {
        $this->geoPoint = $geoPoint;

        return $this;
    }

    public function getLatLon(): ?LatLonDto
    {
        if (null !== $this->lat && null !== $this->lon) {
            $dto = new LatLonDto();
            $dto->lat = (float) $this->lat;
            $dto->lon = (float) $this->lon;

            return $dto;
        }

        if (null !== $this->geoPoint) {
            $dto = new LatLonDto();
            $dto->lat = (float) $this->geoPoint->getLat();
            $dto->lon = (float) $this->geoPoint->getLon();

            return $dto;
        }

        return null;
    }

    public function getGeoNotes(): ?string
    {
        return $this->geoNotes;
    }

    public function setGeoNotes(?string $geoNotes): static
    {
        $this->geoNotes = $geoNotes;

        return $this;
    }

    public function getGeoPlace(bool $withSubdistrict = true): ?string
    {
        if ($this->geoPoint !== null) {
            $place = $this->geoPoint->getLongBeName();
            if (!empty($this->geoPoint->getDistrict())) {
                $place .= ', ' . $this->geoPoint->getDistrict();
            }
            if ($withSubdistrict && !empty($this->geoPoint->getSubdistrict())) {
                $place .= ', ' . $this->geoPoint->getSubdistrict();
            }

            return $place;
        }

        return $this->geoNotes;
    }


    public function getMiddleGeoPlace(bool $withSubdistrict = true): ?string
    {
        if ($this->geoPoint !== null) {
            $place = $this->geoPoint->getMiddleBeName();
            if (!empty($this->geoPoint->getDistrict())) {
                $place .= ', ' . $this->geoPoint->getDistrict();
            }
            if ($withSubdistrict && !empty($this->geoPoint->getSubdistrict())) {
                $place .= ', ' . $this->geoPoint->getShortSubdistrict();
            }

            return $place;
        }

        return $this->geoNotes;
    }

    public function getShortGeoPlace(bool $withPrefix = false): ?string
    {
        if ($this->geoPoint !== null) {
            return $withPrefix ? $this->geoPoint->getMiddleBeName() : $this->geoPoint->getName();
        }

        return $this->geoNotes;
    }

    public function getLat(): ?string
    {
        return $this->lat;
    }

    public function setLat(?string $lat): static
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLon(): ?string
    {
        return $this->lon;
    }

    public function setLon(?string $lon): static
    {
        $this->lon = $lon;

        return $this;
    }

    /**
     * @return Collection<int, UserReport>
     */
    public function getUserReports(): Collection
    {
        return $this->userReports;
    }

    public function addUserReport(UserReport $userReport): static
    {
        if (!$this->userReports->contains($userReport)) {
            $this->userReports->add($userReport);
            $userReport->setReport($this);
        }

        return $this;
    }

    public function removeUserReport(UserReport $userReport): static
    {
        if ($this->userReports->removeElement($userReport)) {
            // set the owning side to null (unless already changed)
            if ($userReport->getReport() === $this) {
                $userReport->setReport(null);
            }
        }

        return $this;
    }

    public function getLeader(): ?User
    {
        foreach ($this->userReports as $userReport) {
            if ($userReport->isLeader()) {
                return $userReport->getParticipant();
            }
        }

        return null;
    }

    /**
     * @return array<string, array<UserReport>>
     */
    public function getUserReportsGroupsByUser(): array
    {
        $results = [];

        foreach ($this->userReports as $userReport) {
            $userId = $userReport->getParticipant()->getId();
            $results[$userId][] = $userReport;
        }

        return $results;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return Collection<int, ReportBlock>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function addBlock(ReportBlock $block): static
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks->add($block);
            $block->setReport($this);
        }

        return $this;
    }

    public function removeBlock(ReportBlock $block): static
    {
        if ($this->blocks->removeElement($block)) {
            // set the owning side to null (unless already changed)
            if ($block->getReport() === $this) {
                $block->setReport(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s, %s',
            $this->expedition->getName(),
            $this->getTextDateAction()
        );
    }

    public function getTemp(): ?array
    {
        return $this->temp;
    }

    public function getTempValue(string $key)
    {
        return $this->temp[$key] ?? null;
    }

    public function setTemp(?array $temp): static
    {
        $this->temp = $temp;

        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setReport($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getReport() === $this) {
                $task->setReport(null);
            }
        }

        return $this;
    }

    public function getAmountTaskQuestions(): int
    {
        return $this->getAmountTasks(TaskStatus::QUESTION);
    }

    public function getAmountTaskTips(): int
    {
        return $this->getAmountTasks(TaskStatus::TIP);
    }

    public function getAmountTasks(?int $status = null): int
    {
        $amount = 0;

        foreach ($this->tasks as $task) {
            if (null === $status || $task->getStatus() === $status) {
                $amount++;
            }
        }

        foreach ($this->blocks as $block) {
            foreach ($block->getTasks() as $task) {
                if (null === $status || $task->getStatus() === $status) {
                    $amount++;
                }
            }
        }

        return $amount;
    }
}
