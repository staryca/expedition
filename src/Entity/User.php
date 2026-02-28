<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Additional\UserRoles;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ApiResource]
class User implements OAuthAwareUserProviderInterface, UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(length: 150)]
    private ?string $firstName = null;

    #[ORM\Column(length: 150)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateJoined = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, UserReport>
     */
    #[ORM\OneToMany(targetEntity: UserReport::class, mappedBy: 'participant', orphanRemoval: true)]
    private Collection $userReports;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $nicks = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 200)]
    private ?string $roles = null;

    public function __construct()
    {
        $this->userReports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function isSameFirstName(string $firstName): bool
    {
        return mb_strtolower($this->firstName) === mb_strtolower($firstName);
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function gelFullName(): string
    {
        return $this->lastName . (empty($this->firstName) ? '' : ' ') . $this->firstName;
    }

    public function getDateJoined(): ?\DateTimeInterface
    {
        return $this->dateJoined;
    }

    public function setDateJoined(\DateTimeInterface $dateJoined): static
    {
        $this->dateJoined = $dateJoined;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getUserReports(): Collection
    {
        return $this->userReports;
    }

    public function setUserReports(Collection $userReports): void
    {
        $this->userReports = $userReports;
    }

    public function getNicks(): ?string
    {
        return $this->nicks;
    }

    public function setNicks(?string $nicks): static
    {
        $this->nicks = $nicks;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf("#%d %s", $this->id, $this->firstName);
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): void
    {
        $this->firstName = $response->getUserIdentifier();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        if (empty($this->roles)) {
            return [UserRoles::ROLE_USER];
        }

        $roles = explode(',', $this->roles);

        if (!in_array(UserRoles::ROLE_USER, $roles, true)) {
            $roles[] = UserRoles::ROLE_USER;
        }

        return $roles;
    }

    public function setRoles(string $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function isAdmin(): bool
    {
        return in_array(UserRoles::ROLE_ADMIN, $this->getRoles(), true);
    }
}
