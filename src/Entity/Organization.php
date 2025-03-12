<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\OrganizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ApiResource]
class Organization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCreated = null;

    #[ORM\ManyToOne]
    private ?GeoPoint $geoPoint = null;

    /**
     * @var Collection<int, OrganizationInformant>
     */
    #[ORM\OneToMany(targetEntity: OrganizationInformant::class, mappedBy: 'organization', orphanRemoval: true)]
    private Collection $organizationInformants;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->organizationInformants = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(?\DateTimeInterface $dateCreated): static
    {
        $this->dateCreated = $dateCreated;

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

    /**
     * @return Collection<int, OrganizationInformant>
     */
    public function getOrganizationInformants(): Collection
    {
        return $this->organizationInformants;
    }

    /**
     * @return array<OrganizationInformant>
     */
    public function getOrganizationInformantsSorted(): array
    {
        $result = [];

        foreach ($this->organizationInformants as $organizationInformant) {
            $result[$organizationInformant->getInformant()->getFirstName() . uniqid('', true)] = $organizationInformant;
        }
        ksort($result);

        return $result;
    }

    public function addOrganizationInformant(OrganizationInformant $organizationInformant): static
    {
        if (!$this->organizationInformants->contains($organizationInformant)) {
            $this->organizationInformants->add($organizationInformant);
            $organizationInformant->setOrganization($this);
        }

        return $this;
    }

    public function removeOrganizationInformant(OrganizationInformant $organizationInformant): static
    {
        $this->organizationInformants->removeElement($organizationInformant);

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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }
}
