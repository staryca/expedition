<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\OrganizationInformantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganizationInformantRepository::class)]
#[ApiResource]
class OrganizationInformant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'organizationInformants')]
    #[ORM\JoinColumn(nullable: false)]
    private Organization $organization;

    #[ORM\ManyToOne(inversedBy: 'organizationInformants')]
    #[ORM\JoinColumn(nullable: false)]
    private Informant $informant;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getInformant(): Informant
    {
        return $this->informant;
    }

    public function setInformant(Informant $informant): static
    {
        $this->informant = $informant;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }
}
