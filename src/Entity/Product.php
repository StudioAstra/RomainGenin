<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Produit ou prestation du catalogue, réutilisable dans les devis.
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Ex. « forfait », « jour », « heure », « page ». */
    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    private string $unit = 'forfait';

    /** Prix unitaire HT en centimes. */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $unitPrice = 0;

    /** Taux de TVA en % (0 en franchise de TVA). */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '0.00'])]
    #[Assert\Range(min: 0, max: 100)]
    private string $vatRate = '0.00';

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit ?? 'forfait';

        return $this;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(?int $unitPrice): static
    {
        $this->unitPrice = $unitPrice ?? 0;

        return $this;
    }

    public function getVatRate(): string
    {
        return $this->vatRate;
    }

    public function setVatRate(string|float|null $vatRate): static
    {
        $this->vatRate = number_format((float) $vatRate, 2, '.', '');

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): static
    {
        $this->active = (bool) $active;

        return $this;
    }
}
