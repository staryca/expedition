<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Type\SubjectType;
use App\Repository\SubjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[ApiResource]
class Subject
{
    public const IS_DIGIT = '+';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'subjects')]
    private ?Expedition $expedition = null;

    #[ORM\ManyToOne(inversedBy: 'subjects')]
    private ?ReportBlock $reportBlock = null;

    #[ORM\Column(nullable: false)]
    private ?int $type = null;

    #[ORM\Column(length: 200)]
    private ?string $name = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $digit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, File>
     */
    #[ORM\OneToMany(targetEntity: File::class, mappedBy: 'subject')]
    #[ORM\OrderBy(['filename' => 'ASC', 'id' => 'ASC'])]
    private Collection $files;

    #[ORM\Column(nullable: true)]
    private ?bool $marked = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasText = null;

    public function __construct()
    {
        $this->files = new ArrayCollection();
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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getTypeName(): string
    {
        return SubjectType::TYPES[$this->type] ?? '[тып ?]';
    }

    public function setType(int $type): static
    {
        $this->type = $type;

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

    public function getExpedition(): ?Expedition
    {
        return $this->expedition;
    }

    public function setExpedition(?Expedition $expedition): static
    {
        $this->expedition = $expedition;

        return $this;
    }

    public function getDigit(): ?string
    {
        return $this->digit;
    }

    public function setDigit(?string $digit): static
    {
        $this->digit = $digit;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;

        return $this;
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
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setSubject($this);
        }

        return $this;
    }

    public function removeFile(File $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            $file->setSubject(null);
        }

        return $this;
    }

    public function getAmountAllMarkersInFiles(): int
    {
        $amount = 0;

        foreach ($this->getFiles() as $file) {
            $amount += $file->getFileMarkers()->count();
        }

        return $amount;
    }

    public function getAmountDecodingsInFileMarkers(): int
    {
        $amount = 0;

        foreach ($this->getFiles() as $file) {
            foreach ($file->getFileMarkers() as $fileMarker) {
                if (null !== $fileMarker->getDecoding()) {
                    $amount++;
                }
            }
        }

        return $amount;

    }

    public function isMarked(): ?bool
    {
        return $this->marked;
    }

    public function setMarked(?bool $marked): static
    {
        $this->marked = $marked;

        return $this;
    }

    public function hasText(): ?bool
    {
        return $this->hasText;
    }

    public function setHasText(?bool $hasText): static
    {
        $this->hasText = $hasText;

        return $this;
    }
}
