<?php

namespace App\Entity;

use App\Enum\Recurrence;
use App\Repository\QuoteLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: QuoteLineRepository::class)]
class QuoteLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Quote $quote = null;

    /** Produit du catalogue : remplit les champs laissés vides à l'enregistrement. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '1.00'])]
    #[Assert\Positive(message: 'Quantité supérieure à 0.')]
    private string $quantity = '1.00';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $unit = null;

    /** Prix unitaire HT en centimes ; null = repris du produit. */
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $unitPrice = null;

    /** Taux de TVA en % ; null = repris du produit (0 sinon). */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?string $vatRate = null;

    /** Null = périodicité du produit choisi, ponctuel sinon. */
    #[ORM\Column(length: 20, nullable: true, enumType: Recurrence::class)]
    private ?Recurrence $recurrence = null;

    #[ORM\Column]
    private int $position = 0;

    /** Titre de la ligne dans l'admin : 1re ligne de la désignation, ou le produit. */
    public function __toString(): string
    {
        $title = trim(strtok((string) $this->description, "\n") ?: '');

        return '' !== $title ? $title : ($this->product?->getName() ?? 'Nouvelle ligne');
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (null === $this->product && (null === $this->description || '' === trim($this->description))) {
            $context->buildViolation('Choisissez un produit ou saisissez une description.')
                ->atPath('description')
                ->addViolation();
        }
        if (null === $this->product && null === $this->unitPrice) {
            $context->buildViolation('Indiquez un prix ou choisissez un produit.')
                ->atPath('unitPrice')
                ->addViolation();
        }
    }

    /** Complète les champs vides avec les valeurs du produit choisi. */
    public function fillFromProduct(): void
    {
        if (null === $this->product) {
            return;
        }
        if (null === $this->description || '' === trim($this->description)) {
            $this->description = trim($this->product->getName()."\n".$this->product->getDescription());
        }
        $this->unit ??= $this->product->getUnit();
        $this->unitPrice ??= $this->product->getUnitPrice();
        $this->vatRate ??= $this->product->getVatRate();
        $this->recurrence ??= $this->product->getRecurrence();
    }

    public function duplicate(): self
    {
        $copy = new self();
        $copy->product = $this->product;
        $copy->description = $this->description;
        $copy->quantity = $this->quantity;
        $copy->unit = $this->unit;
        $copy->unitPrice = $this->unitPrice;
        $copy->vatRate = $this->vatRate;
        $copy->recurrence = $this->recurrence;
        $copy->position = $this->position;

        return $copy;
    }

    public function isRecurring(): bool
    {
        return $this->getEffectiveRecurrence()->isRecurring();
    }

    /** Total HT en centimes. */
    public function getTotalExclTax(): int
    {
        return (int) round((float) $this->quantity * ($this->unitPrice ?? 0));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): static
    {
        $this->quote = $quote;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

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

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string|float|null $quantity): static
    {
        $this->quantity = number_format((float) ($quantity ?? 1), 2, '.', '');

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getUnitPrice(): ?int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(?int $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getVatRate(): string
    {
        return $this->vatRate ?? '0.00';
    }

    public function setVatRate(string|float|null $vatRate): static
    {
        $this->vatRate = null === $vatRate || '' === $vatRate ? null : number_format((float) $vatRate, 2, '.', '');

        return $this;
    }

    /** Valeur saisie : null = selon le produit. */
    public function getRecurrence(): ?Recurrence
    {
        return $this->recurrence;
    }

    public function setRecurrence(?Recurrence $recurrence): static
    {
        $this->recurrence = $recurrence;

        return $this;
    }

    /** Périodicité appliquée : saisie, sinon celle du produit, sinon ponctuel. */
    public function getEffectiveRecurrence(): Recurrence
    {
        return $this->recurrence ?? $this->product?->getRecurrence() ?? Recurrence::OneOff;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position ?? 0;

        return $this;
    }
}
