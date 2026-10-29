<?php

namespace App\Entity;

use App\Repository\QuoteInstallmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Échéance de paiement d'un devis (acompte, échéance intermédiaire, solde), en % du total.
 */
#[ORM\Entity(repositoryClass: QuoteInstallmentRepository::class)]
class QuoteInstallment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'installments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Quote $quote = null;

    /** Ex. « Acompte », « 2e échéance », « Solde ». */
    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    private ?string $label = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0.01, max: 100)]
    private ?string $percent = null;

    /** Ex. « à la signature », « à la livraison », « 30 jours après livraison ». */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $dueText = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column]
    private int $position = 0;

    public function __toString(): string
    {
        return trim(\sprintf('%s · %s %%', $this->label ?? 'Échéance', rtrim(rtrim((string) $this->percent, '0'), '.')));
    }

    public function duplicate(): self
    {
        $copy = new self();
        $copy->label = $this->label;
        $copy->percent = $this->percent;
        $copy->dueText = $this->dueText;
        $copy->position = $this->position;

        return $copy;
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPercent(): ?string
    {
        return $this->percent;
    }

    public function setPercent(string|float|null $percent): static
    {
        $this->percent = null === $percent || '' === $percent ? null : number_format((float) $percent, 2, '.', '');

        return $this;
    }

    public function getDueText(): ?string
    {
        return $this->dueText;
    }

    public function setDueText(?string $dueText): static
    {
        $this->dueText = $dueText;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
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
