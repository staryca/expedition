<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Dto\EpisodeDto;
use App\Entity\Type\CategoryType;
use App\Repository\FileMarkerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ORM\Entity(repositoryClass: FileMarkerRepository::class)]
#[ORM\Table(name: '`file_marker`')]
#[ApiResource]
class FileMarker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fileMarkers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?File $file = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: false)]
    private int $category = CategoryType::OTHER;

    #[ORM\Column(type: 'carbon_immutable', precision: 3, nullable: true)]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'H:i:s.u'])]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: 'carbon_immutable', precision: 3, nullable: true)]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'H:i:s.u'])]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $tags;

    #[ORM\ManyToOne(inversedBy: 'fileMarkers')]
    private ?ReportBlock $reportBlock = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $decoding = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public static function makeFromEpisode(EpisodeDto $episode): FileMarker
    {
        $fileMarker = new self();
        $fileMarker->setNotes($episode->getText());
        $fileMarker->setCategory($episode->getCategory());

        return $fileMarker;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): int
    {
        return $this->category;
    }

    public function getCategoryName(): string
    {
        return CategoryType::TYPES[$this->category] ?? '[тып ?]';
    }

    public function setCategory(int $category): void
    {
        $this->category = $category;
    }

    public function isCategoryNotOther(): bool
    {
        return $this->category !== CategoryType::OTHER;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;

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

    public function getReportBlock(): ?ReportBlock
    {
        return $this->reportBlock;
    }

    public function setReportBlock(?ReportBlock $reportBlock): static
    {
        $this->reportBlock = $reportBlock;

        return $this;
    }

    public function getDecoding(): ?string
    {
        return $this->decoding;
    }

    public function setDecoding(?string $decoding): static
    {
        $this->decoding = $decoding;

        return $this;
    }
}
