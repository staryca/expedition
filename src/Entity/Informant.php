<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Dto\NameGenderDto;
use App\Entity\Type\GenderType;
use App\Repository\InformantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InformantRepository::class)]
#[ApiResource]
class Informant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?GeoPoint $geoPointBirth = null;

    #[ORM\ManyToOne]
    private ?GeoPoint $geoPointCurrent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(nullable: false)]
    private int $gender = GenderType::UNKNOWN;

    #[ORM\Column(nullable: true)]
    private ?int $yearBirth = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dayBirth = null;

    #[ORM\Column(nullable: true)]
    private ?int $yearDied = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isDied = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'Дадатковыя заўвагі пра інфарматара'])]
    private ?string $notes = null;

    #[ORM\Column(length: 1000, nullable: true, options: ['comment' => 'Месца народжэння'])]
    private ?string $placeBirth = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $placeCurrent = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'Год пераезду (напрыклад, пасля шлюбу)'])]
    private ?int $yearTransfer = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => 'Канфесія'])]
    private ?string $confession = null;

    #[ORM\Column(length: 200, nullable: true, options: ['comment' => 'Фотаздымак інфарматара (часова, перанясецца ў іншую табліцу)'])]
    private ?string $pathPhoto = null;

    #[ORM\Column(length: 1000, nullable: true, options: ['comment' => 'Фотаздымак інфарматара URL (часова, перанясецца ў іншую табліцу)'])]
    private ?string $urlPhoto = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCreated = null;

    /**
     * @var Collection<int, OrganizationInformant>
     */
    #[ORM\OneToMany(targetEntity: OrganizationInformant::class, mappedBy: 'informant', orphanRemoval: true)]
    private Collection $organizationInformants;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'informant')]
    private Collection $tasks;

    public function __construct()
    {
        $this->organizationInformants = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

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

    public function getYearBirth(): ?int
    {
        return $this->yearBirth;
    }

    public function setYearBirth(?int $yearBirth): static
    {
        $this->yearBirth = $yearBirth;

        return $this;
    }

    public function getDayBirth(): ?\DateTimeInterface
    {
        return $this->dayBirth;
    }

    public function setDayBirth(?\DateTimeInterface $dayBirth): static
    {
        $this->dayBirth = $dayBirth;

        return $this;
    }

    public function getLifeDates(): ?string
    {
        if ($this->dayBirth) {
            $from = $this->dayBirth->format('d.m.Y');
        } elseif ($this->yearBirth) {
            $from = $this->yearBirth;
        } else {
            $from = "?";
        }

        if ($this->yearDied) {
            $to = $this->yearDied;
        } elseif ($this->isDied) {
            $to = "died";
        } else {
            $to = "?";
        }

        $result = $from . '-' . $to;
        if ($result === '?-?') {
            return null;
        }
        if ($result === '?-died') {
            $result = 'died';
        }

        return str_replace('died', $this->getDiedText(), $result);
    }

    public function getDiedText(): string
    {
        return 'пам' . ($this->gender !== GenderType::FEMALE ? 'ё' : 'е')
        . 'р' . ($this->gender !== GenderType::UNKNOWN ? '' : '/') . ($this->gender !== GenderType::MALE ? 'ла' : '');
    }

    public function getYearDied(): ?int
    {
        return $this->yearDied;
    }

    public function setYearDied(?int $yearDied): static
    {
        $this->yearDied = $yearDied;

        return $this;
    }

    public function isDied(): ?bool
    {
        return $this->isDied;
    }

    public function setDied(?bool $isDied): static
    {
        $this->isDied = $isDied;

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

    public function getYearTransfer(): ?int
    {
        return $this->yearTransfer;
    }

    public function setYearTransfer(?int $yearTransfer): static
    {
        $this->yearTransfer = $yearTransfer;

        return $this;
    }

    public function getGender(): int
    {
        return $this->gender;
    }

    public function setGender(int $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getNameAndGender(): NameGenderDto
    {
        return new NameGenderDto($this->firstName, $this->gender);
    }

    /**
     * @param NameGenderDto $nameAndGender
     * @return bool if name or gender were changed
     */
    public function setNameAndGender(NameGenderDto $nameAndGender): bool
    {
        $isChanged = false;

        if ($this->firstName !== $nameAndGender->getName()) {
            $this->firstName = $nameAndGender->getName();
            $isChanged = true;
        }

        if ($this->gender !== $nameAndGender->gender) {
            $this->gender = $nameAndGender->gender;
            $isChanged = true;
        }

        return $isChanged;
    }

    public function getConfession(): ?string
    {
        return $this->confession;
    }

    public function setConfession(?string $confession): static
    {
        $this->confession = $confession;

        return $this;
    }

    public function getPlaceBirth(): ?string
    {
        return $this->placeBirth;
    }

    public function setPlaceBirth(?string $placeBirth): static
    {
        $this->placeBirth = $placeBirth;

        return $this;
    }

    public function getPlaceCurrent(): ?string
    {
        return $this->placeCurrent;
    }

    public function setPlaceCurrent(?string $placeCurrent): static
    {
        $this->placeCurrent = $placeCurrent;

        return $this;
    }

    public function getPathPhoto(): ?string
    {
        return $this->pathPhoto;
    }

    public function setPathPhoto(?string $pathPhoto): static
    {
        $this->pathPhoto = $pathPhoto;

        return $this;
    }

    public function getUrlPhoto(): ?string
    {
        return $this->urlPhoto;
    }

    public function setUrlPhoto(?string $urlPhoto): static
    {
        $this->urlPhoto = $urlPhoto;

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

    public function getGeoPointBirth(): ?GeoPoint
    {
        return $this->geoPointBirth;
    }

    public function setGeoPointBirth(?GeoPoint $geoPointBirth): static
    {
        $this->geoPointBirth = $geoPointBirth;

        return $this;
    }

    public function getGeoPointCurrent(): ?GeoPoint
    {
        return $this->geoPointCurrent;
    }

    public function setGeoPointCurrent(?GeoPoint $geoPointCurrent): static
    {
        $this->geoPointCurrent = $geoPointCurrent;

        return $this;
    }

    public function getCurrentPlaceBe(): string
    {
        return trim($this->geoPointCurrent?->getLongBeName() . ' ' . $this->placeCurrent);
    }

    public function getBirthPlaceBe(): string
    {
        return trim($this->geoPointBirth?->getLongBeName() . ' ' . $this->placeBirth);
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
            $task->setInformant($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getInformant() === $this) {
                $task->setInformant(null);
            }
        }

        return $this;
    }

    public function getSearchIndex(): string
    {
        $text = $this->getFirstName() . ', ';
        if ($this->getLifeDates() !== null) {
            $text .= $this->getLifeDates() . ', ';
        }
        if (!empty($this->getBirthPlaceBe())) {
            $text .= 'з ' . $this->getBirthPlaceBe() . ', ';
        }
        if (!empty($this->getCurrentPlaceBe())) {
            $text .= 'зараз у ' . $this->getCurrentPlaceBe() . ', ';
        }
        if ($this->getNotes() !== null) {
            $text .= $this->getNotes() . ', ';
        }

        return trim($text, " ,.");
    }

    public function getOrganizationInformants(): Collection
    {
        return $this->organizationInformants;
    }

    public function addOrganizationInformant(OrganizationInformant $organizationInformant): static
    {
        if (!$this->organizationInformants->contains($organizationInformant)) {
            $this->organizationInformants->add($organizationInformant);
            $organizationInformant->setInformant($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getFirstName();
    }
}
