<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Type\FileType;
use App\Repository\FileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ApiResource]
class File
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    private ?Subject $subject = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    private ?ReportBlock $reportBlock = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $path = null;

    #[ORM\Column]
    private ?int $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private ?bool $isProcessed = null;

    /**
     * @var Collection<int, FileMarker>
     */
    #[ORM\OneToMany(targetEntity: FileMarker::class, mappedBy: 'file', orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $fileMarkers;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $tags;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $sizeText = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isDeny = null;

    #[ORM\Column(nullable: true)]
    private ?array $additional = null;

    public function __construct()
    {
        $this->fileMarkers = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getFullFileName(): string
    {
        return ($this->path ? $this->path . '/' : '') . ($this->filename ?? '');
    }

    public function isProcessed(): ?bool
    {
        return $this->isProcessed;
    }

    public function setProcessed(bool $isProcessed): static
    {
        $this->isProcessed = $isProcessed;

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

    /**
     * @return Collection<int, FileMarker>
     */
    public function getFileMarkers(): Collection
    {
        return $this->fileMarkers;
    }

    public function addFileMarker(FileMarker $fileMarker): static
    {
        if (!$this->fileMarkers->contains($fileMarker)) {
            $this->fileMarkers->add($fileMarker);
            $fileMarker->setFile($this);
        }

        return $this;
    }

    public function removeFileMarker(FileMarker $fileMarker): static
    {
        if ($this->fileMarkers->removeElement($fileMarker)) {
            // set the owning side to null (unless already changed)
            if ($fileMarker->getFile() === $this) {
                $fileMarker->setFile(null);
            }
        }

        return $this;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(?Subject $subject): static
    {
        $this->subject = $subject;

        return $this;
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function isDeny(): ?bool
    {
        return $this->isDeny;
    }

    public function setDeny(?bool $isDeny): static
    {
        $this->isDeny = $isDeny;

        return $this;
    }

    public function getSizeText(): ?string
    {
        return $this->sizeText;
    }

    public function setSizeText(?string $sizeText): static
    {
        $this->sizeText = $sizeText;

        return $this;
    }

    /**
     * @param bool $partMarkers
     * @param array<FileMarker> $markers
     * @return string
     */
    public function getSearchIndex(bool $partMarkers = false, array $markers = []): string
    {
        $text = '';

        if ($this->type === FileType::TYPE_VIRTUAL_CONTENT_LIST) {
            foreach ($this->getFileMarkers() as $fileMarker) {
                if (!empty($fileMarker->getName())) {
                    $text .= $fileMarker->getName() . ', ';
                }
                foreach ($fileMarker->getTags() as $tag) {
                    $text .= $tag->getName() . ', ';
                }
                if (!empty($fileMarker->getNotes())) {
                    $text .= trim($fileMarker->getNotes(), " ,.;") . ', ';
                }
                $text .= trim($text, " ,.;") . '; ';
            }
            $text = trim($text, " ,;");
        } else {
            if ($this->filename) {
                $text .= $this->filename . ', ';
            }
            if ($this->sizeText) {
                $text .= $this->sizeText . ', ';
            }
            if ($this->comment) {
                $text .= trim($this->comment, " ,.;") . ', ';
            }
            if (!empty($text)) {
                $text = trim($text, " ,.;") . '. ';
            }

            $printMarkers = $partMarkers ? $markers : $this->getFileMarkers();
            foreach ($printMarkers as $fileMarker) {
                if ($fileMarker->getStartTime()) {
                    $text .= $fileMarker->getStartTime()->format('H:i:s.u') . ' ';
                }
                if (!empty($fileMarker->getName())) {
                    $text .= $fileMarker->getName() . ', ';
                }
                foreach ($fileMarker->getTags() as $tag) {
                    $text .= $tag->getName() . ', ';
                }
                if (!empty($fileMarker->getNotes())) {
                    $text .= trim($fileMarker->getNotes(), " ,.;") . ', ';
                }
                if (!empty($fileMarker->getDecoding())) {
                    $text .= trim($fileMarker->getDecoding(), " ,.;") . ', ';
                }
                $text .= trim($text, " ,.;") . '; ';
            }
            $text = trim($text, " ,;");
        }

        return $text;
    }

    public function getAdditional(): ?array
    {
        return $this->additional;
    }

    public function setAdditional(?array $additional): static
    {
        $this->additional = $additional;

        return $this;
    }
}
