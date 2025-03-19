<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Type\GeoPointType;
use App\Repository\GeoPointRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GeoPointRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
class GeoPoint
{
    #[ORM\Id]
    #[ORM\Column(type: Types::BIGINT, updatable: false)]
    private string $id;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 6)]
    private ?string $lat = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 6)]
    private ?string $lon = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $district = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nameWordStress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subdistrict = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nameRu = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $prefixRu = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $prefixBe = null;

    #[ORM\Column(nullable: true)]
    private ?int $regionId = null;

    #[ORM\Column(nullable: true)]
    private ?int $departmentId = null;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function getLat(): ?string
    {
        return $this->lat;
    }

    public function setLat(string $lat): static
    {
        $this->lat = $lat;

        return $this;
    }

    public function getLon(): ?string
    {
        return $this->lon;
    }

    public function setLon(string $lon): static
    {
        $this->lon = $lon;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): static
    {
        $this->district = $district;

        return $this;
    }

    public function getNameWordStress(): ?string
    {
        return $this->nameWordStress;
    }

    public function setNameWordStress(?string $nameWordStress): static
    {
        $this->nameWordStress = $nameWordStress;

        return $this;
    }

    public function getSubdistrict(): ?string
    {
        return $this->subdistrict;
    }

    public function setSubdistrict(?string $subdistrict): static
    {
        $this->subdistrict = $subdistrict;

        return $this;
    }

    public function getNameRu(): ?string
    {
        return $this->nameRu;
    }

    public function setNameRu(?string $nameRu): static
    {
        $this->nameRu = $nameRu;

        return $this;
    }

    public function getPrefixRu(): ?string
    {
        return $this->prefixRu;
    }

    public function setPrefixRu(?string $prefixRu): static
    {
        $this->prefixRu = $prefixRu;

        return $this;
    }

    public function getPrefixBe(): ?string
    {
        return $this->prefixBe;
    }

    public function setPrefixBe(?string $prefixBe): static
    {
        $this->prefixBe = $prefixBe;

        return $this;
    }

    public function getShortPrefixBe(): string
    {
        return GeoPointType::getShortName($this->prefixBe);
    }

    public function getLongBeName(): string
    {
        return ($this->prefixBe ? $this->prefixBe . ' ' : '') . ($this->name ? (' ' . $this->name) : '');
    }

    public function getFullBeName(bool $withRegion = false): string
    {
        $name = sprintf('%s %s', $this->getShortPrefixBe(), $this->name);

        if ($this->district) {
            $name .= ', ' . $this->district;
        }
        if ($withRegion) {
            $name .= ', ' . $this->region;
        }
        if ($this->subdistrict) {
            $name .= ' (' . str_replace('сельскі Савет', 'с/с', $this->subdistrict) . ')';
        }

        return $name;
    }

    public function __toString(): string
    {
        return sprintf('%s %s (%s, %s)', $this->prefixBe, $this->name, $this->region, $this->district);
    }

    public function getRegionId(): ?int
    {
        return $this->regionId;
    }

    public function setRegionId(?int $regionId): static
    {
        $this->regionId = $regionId;

        return $this;
    }

    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    public function setDepartmentId(?int $departmentId): static
    {
        $this->departmentId = $departmentId;

        return $this;
    }
}
