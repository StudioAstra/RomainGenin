<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Émetteur des devis : informations légales de l'entreprise (une seule ligne en base).
 */
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    public const DEFAULT_CONDITIONS = "Acompte de 30 % à la signature, solde à la livraison.\nPaiement par virement bancaire à 30 jours.\nEn cas de retard de paiement, indemnité forfaitaire pour frais de recouvrement de 40 € (art. D. 441-5 du Code de commerce).";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank]
    private ?string $name = null;

    /** Ex. « Entrepreneur individuel » ou « Micro-entreprise ». */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $legalForm = 'Entrepreneur individuel';

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex('/^[0-9 ]{9,17}$/', message: 'SIRET : 14 chiffres.')]
    private ?string $siret = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $iban = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $bic = null;

    /** Franchise en base de TVA (auto-entrepreneur) : mention légale sur le devis. */
    #[ORM\Column(options: ['default' => true])]
    private bool $vatExempt = true;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $vatNumber = null;

    #[ORM\Column(options: ['default' => 30])]
    #[Assert\Positive]
    private int $quoteValidityDays = 30;

    /** Conditions reprises sur chaque nouveau devis (modifiables devis par devis). */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $quoteConditions = self::DEFAULT_CONDITIONS;

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getId(): ?int
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

    public function getLegalForm(): ?string
    {
        return $this->legalForm;
    }

    public function setLegalForm(?string $legalForm): static
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

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

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): static
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): static
    {
        $this->bic = $bic;

        return $this;
    }

    public function isVatExempt(): bool
    {
        return $this->vatExempt;
    }

    public function setVatExempt(?bool $vatExempt): static
    {
        $this->vatExempt = (bool) $vatExempt;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): static
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getQuoteValidityDays(): int
    {
        return $this->quoteValidityDays;
    }

    public function setQuoteValidityDays(?int $quoteValidityDays): static
    {
        $this->quoteValidityDays = $quoteValidityDays ?? 30;

        return $this;
    }

    public function getQuoteConditions(): ?string
    {
        return $this->quoteConditions;
    }

    public function setQuoteConditions(?string $quoteConditions): static
    {
        $this->quoteConditions = $quoteConditions;

        return $this;
    }
}
