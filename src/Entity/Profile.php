<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Informations générales du site (une seule ligne en base).
 */
#[ORM\Entity(repositoryClass: ProfileRepository::class)]
class Profile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    /** Poste occupé, ex. « Software Engineer Front-End ». */
    #[ORM\Column(length: 160)]
    #[Assert\NotBlank]
    private ?string $jobTitle = null;

    /** Seconde ligne sous le poste, ex. « Adobe Commerce / Symfony ». */
    #[ORM\Column(length: 160, nullable: true)]
    private ?string $jobSubtitle = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $intro = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $linkedinUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $maltUrl = null;

    /** Disponible pour de nouvelles missions (badge en haut de page et dans le contact). */
    #[ORM\Column(options: ['default' => true])]
    private bool $available = true;

    /** Texte du badge ; vide = libellé par défaut selon la disponibilité. */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $availabilityLabel = null;

    /** Mode maintenance : les visiteurs voient une page d'attente (CV + contact), l'admin voit le site. */
    #[ORM\Column(options: ['default' => false])]
    private bool $maintenance = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $maintenanceMessage = null;

    /** Affiche le lien Malt (menu + contact). */
    #[ORM\Column(options: ['default' => true])]
    private bool $showMalt = true;

    /** CV en PDF, téléchargeable depuis le menu et le contact. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvFile = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $skills = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $hobbies = [];

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
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

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getJobSubtitle(): ?string
    {
        return $this->jobSubtitle;
    }

    public function setJobSubtitle(?string $jobSubtitle): static
    {
        $this->jobSubtitle = $jobSubtitle;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getIntro(): ?string
    {
        return $this->intro;
    }

    public function setIntro(?string $intro): static
    {
        $this->intro = $intro;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->linkedinUrl;
    }

    public function setLinkedinUrl(?string $linkedinUrl): static
    {
        $this->linkedinUrl = $linkedinUrl;

        return $this;
    }

    public function getMaltUrl(): ?string
    {
        return $this->maltUrl;
    }

    public function setMaltUrl(?string $maltUrl): static
    {
        $this->maltUrl = $maltUrl;

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(?bool $available): static
    {
        $this->available = (bool) $available;

        return $this;
    }

    public function getAvailabilityLabel(): ?string
    {
        return $this->availabilityLabel;
    }

    public function setAvailabilityLabel(?string $availabilityLabel): static
    {
        $this->availabilityLabel = $availabilityLabel;

        return $this;
    }

    public function getAvailabilityText(): string
    {
        return $this->availabilityLabel
            ?: ($this->available ? 'Disponible pour de nouvelles missions' : 'Actuellement indisponible');
    }

    public function isMaintenance(): bool
    {
        return $this->maintenance;
    }

    public function setMaintenance(?bool $maintenance): static
    {
        $this->maintenance = (bool) $maintenance;

        return $this;
    }

    public function getMaintenanceMessage(): ?string
    {
        return $this->maintenanceMessage;
    }

    public function setMaintenanceMessage(?string $maintenanceMessage): static
    {
        $this->maintenanceMessage = $maintenanceMessage;

        return $this;
    }

    public function isShowMalt(): bool
    {
        return $this->showMalt;
    }

    public function setShowMalt(?bool $showMalt): static
    {
        $this->showMalt = (bool) $showMalt;

        return $this;
    }

    /** Lien Malt à afficher, ou null si désactivé ou vide. */
    public function getVisibleMaltUrl(): ?string
    {
        return $this->showMalt ? $this->maltUrl : null;
    }

    public function getCvFile(): ?string
    {
        return $this->cvFile;
    }

    public function setCvFile(?string $cvFile): static
    {
        $this->cvFile = $cvFile;

        return $this;
    }

    /** @return list<string> */
    public function getSkills(): array
    {
        return $this->skills;
    }

    /** @param list<string>|null $skills */
    public function setSkills(?array $skills): static
    {
        $this->skills = array_values(array_filter($skills ?? [], static fn ($s) => '' !== trim((string) $s)));

        return $this;
    }

    /** @return list<string> */
    public function getHobbies(): array
    {
        return $this->hobbies;
    }

    /** @param list<string>|null $hobbies */
    public function setHobbies(?array $hobbies): static
    {
        $this->hobbies = array_values(array_filter($hobbies ?? [], static fn ($s) => '' !== trim((string) $s)));

        return $this;
    }
}
