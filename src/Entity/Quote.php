<?php

namespace App\Entity;

use App\Enum\QuoteStatus;
use App\Enum\Recurrence;
use App\Repository\QuoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
class Quote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Ex. DEV-2026-001, attribué au premier enregistrement. */
    #[ORM\Column(length: 30, unique: true, nullable: true)]
    private ?string $number = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Choisissez un client.')]
    private ?Client $client = null;

    #[ORM\Column(length: 20, enumType: QuoteStatus::class)]
    private QuoteStatus $status = QuoteStatus::Draft;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $issueDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'issueDate', message: 'La validité doit suivre la date d\'émission.')]
    private ?\DateTimeImmutable $validUntil = null;

    /** Texte d'introduction affiché avant le tableau. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $introduction = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditions = null;

    /** @var Collection<int, QuoteLine> */
    #[ORM\OneToMany(targetEntity: QuoteLine::class, mappedBy: 'quote', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins une ligne au devis.')]
    private Collection $lines;

    /**
     * Échéancier (paiement en plusieurs fois) ; vide = paiement en une fois.
     *
     * @var Collection<int, QuoteInstallment>
     */
    #[ORM\OneToMany(targetEntity: QuoteInstallment::class, mappedBy: 'quote', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Assert\Valid]
    private Collection $installments;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->installments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->issueDate = new \DateTimeImmutable('today');
    }

    public function __toString(): string
    {
        return trim(($this->number ?? 'Devis').' · '.$this->title);
    }

    /** Copie en brouillon, sans numéro, datée du jour. */
    public function duplicate(): self
    {
        $copy = new self();
        $copy->title = $this->title;
        $copy->client = $this->client;
        $copy->introduction = $this->introduction;
        $copy->conditions = $this->conditions;
        foreach ($this->lines as $line) {
            $copy->addLine($line->duplicate());
        }
        foreach ($this->installments as $installment) {
            $copy->addInstallment($installment->duplicate());
        }

        return $copy;
    }

    #[Assert\Callback]
    public function validateInstallments(ExecutionContextInterface $context): void
    {
        if ($this->installments->isEmpty()) {
            return;
        }

        $sum = array_sum($this->installments->map(static fn (QuoteInstallment $i) => (float) $i->getPercent())->toArray());
        if (abs($sum - 100) > 0.001) {
            $context->buildViolation(\sprintf('Le total des échéances doit faire 100 %% (actuellement %s %%).', rtrim(rtrim(number_format($sum, 2, ',', ''), '0'), ',')))
                ->atPath('installments')
                ->addViolation();
        }
    }

    /** @return list<QuoteLine> Lignes facturées une fois. */
    public function getOneOffLines(): array
    {
        return array_values($this->lines->filter(static fn (QuoteLine $l) => !$l->isRecurring())->toArray());
    }

    /** @return list<QuoteLine> Lignes d'abonnement (mensuel, annuel…). */
    public function getRecurringLines(): array
    {
        return array_values($this->lines->filter(static fn (QuoteLine $l) => $l->isRecurring())->toArray());
    }

    /**
     * Totaux des abonnements par périodicité, en centimes.
     *
     * @return array<string, array{recurrence: Recurrence, exclTax: int, vat: int, inclTax: int}>
     */
    public function getRecurringTotals(): array
    {
        $totals = [];
        foreach (Recurrence::recurring() as $recurrence) {
            $lines = array_filter($this->getRecurringLines(), static fn (QuoteLine $l) => $l->getEffectiveRecurrence() === $recurrence);
            if ([] === $lines) {
                continue;
            }
            $exclTax = array_sum(array_map(static fn (QuoteLine $l) => $l->getTotalExclTax(), $lines));
            $vat = array_sum(array_map(static fn (QuoteLine $l) => (int) round($l->getTotalExclTax() * (float) $l->getVatRate() / 100), $lines));
            $totals[$recurrence->value] = ['recurrence' => $recurrence, 'exclTax' => $exclTax, 'vat' => $vat, 'inclTax' => $exclTax + $vat];
        }

        return $totals;
    }

    /**
     * Montant de chaque échéance sur le total à payer (TTC, ou HT en franchise de TVA,
     * puisque la TVA est alors nulle). La dernière échéance absorbe l'arrondi.
     *
     * @return list<array{installment: QuoteInstallment, amount: int}>
     */
    public function getPaymentSchedule(): array
    {
        $total = $this->getTotalInclTax();
        $schedule = [];
        $allocated = 0;
        $installments = array_values($this->installments->toArray());
        foreach ($installments as $i => $installment) {
            $amount = $i === \count($installments) - 1
                ? $total - $allocated
                : (int) round($total * (float) $installment->getPercent() / 100);
            $allocated += $amount;
            $schedule[] = ['installment' => $installment, 'amount' => $amount];
        }

        return $schedule;
    }

    public function getTotalExclTax(): int
    {
        return array_sum(array_map(static fn (QuoteLine $l) => $l->getTotalExclTax(), $this->getOneOffLines()));
    }

    /**
     * TVA des lignes ponctuelles regroupée par taux, en centimes.
     *
     * @return array<string, array{base: int, vat: int}>
     */
    public function getVatBreakdown(): array
    {
        $breakdown = [];
        foreach ($this->getOneOffLines() as $line) {
            $rate = $line->getVatRate();
            $breakdown[$rate] ??= ['base' => 0, 'vat' => 0];
            $breakdown[$rate]['base'] += $line->getTotalExclTax();
        }
        foreach ($breakdown as $rate => &$row) {
            $row['vat'] = (int) round($row['base'] * (float) $rate / 100);
        }
        ksort($breakdown, \SORT_NUMERIC);

        return $breakdown;
    }

    public function getTotalVat(): int
    {
        return array_sum(array_column($this->getVatBreakdown(), 'vat'));
    }

    public function getTotalInclTax(): int
    {
        return $this->getTotalExclTax() + $this->getTotalVat();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getStatus(): QuoteStatus
    {
        return $this->status;
    }

    public function setStatus(?QuoteStatus $status): static
    {
        $this->status = $status ?? QuoteStatus::Draft;

        return $this;
    }

    public function getIssueDate(): ?\DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function setIssueDate(?\DateTimeImmutable $issueDate): static
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(?\DateTimeImmutable $validUntil): static
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(?string $introduction): static
    {
        $this->introduction = $introduction;

        return $this;
    }

    public function getConditions(): ?string
    {
        return $this->conditions;
    }

    public function setConditions(?string $conditions): static
    {
        $this->conditions = $conditions;

        return $this;
    }

    /** @return Collection<int, QuoteLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(QuoteLine $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setQuote($this);
        }

        return $this;
    }

    public function removeLine(QuoteLine $line): static
    {
        if ($this->lines->removeElement($line) && $line->getQuote() === $this) {
            $line->setQuote(null);
        }

        return $this;
    }

    /** @return Collection<int, QuoteInstallment> */
    public function getInstallments(): Collection
    {
        return $this->installments;
    }

    public function addInstallment(QuoteInstallment $installment): static
    {
        if (!$this->installments->contains($installment)) {
            $this->installments->add($installment);
            $installment->setQuote($this);
        }

        return $this;
    }

    public function removeInstallment(QuoteInstallment $installment): static
    {
        if ($this->installments->removeElement($installment) && $installment->getQuote() === $this) {
            $installment->setQuote(null);
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
