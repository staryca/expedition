<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Type\FileType;
use App\Entity\Type\GeoPointType;
use App\Entity\Type\ReportBlockType;
use App\Entity\Type\TaskStatus;
use App\Repository\ReportBlockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportBlockRepository::class)]
#[ApiResource]
class ReportBlock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    private int $type = ReportBlockType::TYPE_UNDEFINED;

    #[ORM\ManyToOne(inversedBy: 'blocks')]
    #[ORM\JoinColumn(nullable: false)]
    private Report $report;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreated = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    private ?Organization $organization = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $videoNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $photoNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userNotes = null;

    /**
     * @var Collection<int, File>
     */
    #[ORM\OneToMany(targetEntity: File::class, mappedBy: 'reportBlock')]
    private Collection $files;

    /**
     * @var Collection<int, Subject>
     */
    #[ORM\OneToMany(targetEntity: Subject::class, mappedBy: 'reportBlock')]
    private Collection $subjects;

    /**
     * @var Collection<int, Informant>
     */
    #[ORM\ManyToMany(targetEntity: Informant::class)]
    #[ORM\OrderBy(['firstName' => 'ASC', 'id' => 'ASC'])]
    private Collection $informants;

    #[ORM\Column(nullable: true)]
    private ?array $additional = null;

    /**
     * @var Collection<int, FileMarker>
     */
    #[ORM\OneToMany(targetEntity: FileMarker::class, mappedBy: 'reportBlock', orphanRemoval: true)]
    #[ORM\OrderBy(['file' => 'ASC', 'id' => 'ASC'])]
    private Collection $fileMarkers;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'reportBlock')]
    private Collection $tasks;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $searchIndex = null;

    /* For search results */
    private ?string $searchHeadline = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'reportBlocks')]
    private Collection $tags;

    public function __construct()
    {
        $this->files = new ArrayCollection();
        $this->subjects = new ArrayCollection();
        $this->informants = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getVideoNotes(): ?string
    {
        return $this->videoNotes;
    }

    public function setVideoNotes(?string $videoNotes): static
    {
        $this->videoNotes = $videoNotes;

        return $this;
    }

    public function getPhotoNotes(): ?string
    {
        return $this->photoNotes;
    }

    public function setPhotoNotes(?string $photoNotes): static
    {
        $this->photoNotes = $photoNotes;

        return $this;
    }

    public function getUserNotes(): ?string
    {
        return $this->userNotes;
    }

    public function setUserNotes(?string $userNotes): static
    {
        $this->userNotes = $userNotes;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getTypeName(): string
    {
        return ReportBlockType::TYPES[$this->type] ?? '[тып ?]';
    }

    public function getTypeCorrectName(): string
    {
        $result = $this->getTypeName();

        $geoPoint = $this->getReport()?->getGeoPoint();
        if ($this->type === ReportBlockType::TYPE_VILLAGE_TOUR && null !== $geoPoint) {
            [$result] = explode(' ', $result);
            $result .= ' ' . $geoPoint->getShortPrefixBe() . ' ' . $geoPoint->getName();
        }

        return $result;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function getContentFile(): ?File
    {
        foreach ($this->getFiles() as $file) {
            if ($file->getType() === FileType::TYPE_VIRTUAL_CONTENT_LIST) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, File>
     */
    public function getRealFiles(): Collection
    {
        $result = new ArrayCollection();

        foreach ($this->getFiles() as $file) {
            if ($file->getType() !== FileType::TYPE_VIRTUAL_CONTENT_LIST) {
                $result->add($file);
            }
        }

        return $result;
    }

    public function getRealFilesInGroups(): array
    {
        $result = [];

        foreach ($this->getFiles() as $file) {
            if ($file->getType() !== FileType::TYPE_VIRTUAL_CONTENT_LIST) {
                $result[$file->getType()][] = $file;
            }
        }
        ksort($result);

        return $result;
    }

    public function addFile(File $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setReportBlock($this);
        }

        return $this;
    }

    public function removeFile(File $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            $file->setReportBlock(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjects(): Collection
    {
        return $this->subjects;
    }

    public function addSubject(Subject $subject): static
    {
        if (!$this->subjects->contains($subject)) {
            $this->subjects->add($subject);
            $subject->setReportBlock($this);
        }

        return $this;
    }

    public function removeSubject(Subject $subject): static
    {
        if ($this->subjects->removeElement($subject)) {
            // set the owning side to null (unless already changed)
            if ($subject->getReportBlock() === $this) {
                $subject->setReportBlock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Informant>
     */
    public function getInformants(): Collection
    {
        return $this->informants;
    }

    public function addInformant(Informant $informant): static
    {
        if (!$this->informants->contains($informant)) {
            $this->informants->add($informant);
        }

        return $this;
    }

    public function existsInformant(Informant $informant): bool
    {
        return $this->informants->contains($informant);
    }

    public function removeInformant(Informant $informant): static
    {
        $this->informants->removeElement($informant);

        return $this;
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

    public function getAdditional(): ?array
    {
        return $this->additional;
    }

    public function setAdditional(?array $additional): void
    {
        $this->additional = $additional;
    }

    /**
     * @return Collection<int, FileMarker>
     */
    public function getFileMarkers(): Collection
    {
        return $this->fileMarkers;
    }

    public function getFirstFileOfMarkers(): ?File
    {
        $fileMarker = $this->fileMarkers->first();
        if (!($fileMarker instanceof FileMarker)) {
            return null;
        }

        return $fileMarker->getFile();
    }

    /**
     * @return array<int, array<FileMarker>>
     */
    public function getFileMarkerGroups(): array
    {
        $result = [];

        foreach ($this->fileMarkers as $fileMarker) {
            if (null !== $fileMarker->getFile()) {
                $result[$fileMarker->getFile()->getId()][] = $fileMarker;
            }
        }

        return $result;
    }


    /**
     * @return array<int, File>
     */
    public function getFilesOfMarkers(): array
    {
        $result = [];

        foreach ($this->fileMarkers as $fileMarker) {
            $file = $fileMarker->getFile();
            if (null !== $file && !isset($result[$file->getId()])) {
                $result[$file->getId()] = $file;
            }
        }

        return $result;
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
            $task->setReportBlock($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getReportBlock() === $this) {
                $task->setReportBlock(null);
            }
        }

        return $this;
    }

    public function getSearchIndex(): ?string
    {
        return $this->searchIndex;
    }

    public function setSearchIndex(?string $searchIndex): void
    {
        $this->searchIndex = $searchIndex;
    }

    public function getSearchHeadline(): ?string
    {
        return $this->searchHeadline;
    }

    public function setSearchHeadline(?string $searchHeadline): void
    {
        $this->searchHeadline = $searchHeadline;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
