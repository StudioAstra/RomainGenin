<?php

namespace App\Entity;

use App\Enum\Recurrence;
use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abonnement d'un client (hébergement, nom de domaine, maintenance…) à renouveler.
 */
#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Index(columns: ['next_renewal_date'])]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Choisissez un client.')]
    private ?Client $client = null;

    /** Ex. « Hébergement VPS », « Domaine menuiserie-dupont.fr ». */
    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private ?string $label = null;

    #[ORM\Column(length: 20, enumType: Recurrence::class)]
    #[Assert\Choice(callback: [Recurrence::class, 'recurring'], message: 'Choisissez une périodicité.')]
    private Recurrence $recurrence = Recurrence::Monthly;

    /** Prix facturé HT par période, en centimes. */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $amount = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '0.00'])]
    #[Assert\Range(min: 0, max: 100)]
    private string $vatRate = '0.00';

    /** Ce que l'abonnement vous coûte par période (fournisseur), en centimes. */
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $costPrice = null;

    /** Ex. OVHcloud, Gandi, o2switch. */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $supplier = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $nextRenewalDate = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    /** Ligne de devis d'origine (évite de créer deux fois le même abonnement). */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?QuoteLine $quoteLine = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->startDate = new \DateTimeImmutable('today');
    }

    public function __toString(): string
    {
        return (string) $this->label;
    }

    public static function fromQuoteLine(QuoteLine $line, \DateTimeImmutable $start): self
    {
        $subscription = new self();
        $subscription->client = $line->getQuote()?->getClient();
        $subscription->label = trim(strtok((string) $line->getDescription(), "\n") ?: (string) $line->getProduct()?->getName());
        $subscription->recurrence = $line->getEffectiveRecurrence();
        $subscription->amount = $line->getTotalExclTax();
        $subscription->vatRate = $line->getVatRate();
        $subscription->startDate = $start;
        $subscription->nextRenewalDate = $start->modify($line->getEffectiveRecurrence()->interval());
        $subscription->quoteLine = $line;

        return $subscription;
    }

    /** Marque la période comme renouvelée : prochaine échéance + 1 période. */
    public function renew(): void
    {
        $this->nextRenewalDate = ($this->nextRenewalDate ?? new \DateTimeImmutable('today'))->modify($this->recurrence->interval());
    }

    /** Jours avant renouvellement (négatif = en retard). */
    public function getDaysUntilRenewal(?\DateTimeImmutable $today = null): ?int
    {
        if (null === $this->nextRenewalDate) {
            return null;
        }
        $today ??= new \DateTimeImmutable('today');

        return (int) $today->diff($this->nextRenewalDate)->format('%r%a');
    }

    /** Libellé d'échéance pour la liste : « Dans 12 j », « En retard de 3 j »… */
    public function getRenewalStatus(?\DateTimeImmutable $today = null): string
    {
        if (!$this->active) {
            return 'Résilié';
        }

        $days = $this->getDaysUntilRenewal($today) ?? 0;

        return match (true) {
            $days < 0 => \sprintf('En retard de %d j', -$days),
            0 === $days => 'Aujourd\'hui',
            default => \sprintf('Dans %d j', $days),
        };
    }

    /** Chiffre d'affaires annuel HT, en centimes. */
    public function getYearlyAmount(): int
    {
        return $this->amount * $this->recurrence->perYear();
    }

    /** Marge par période, en centimes (null si coût inconnu). */
    public function getMargin(): ?int
    {
        return null === $this->costPrice ? null : $this->amount - $this->costPrice;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getRecurrence(): Recurrence
    {
        return $this->recurrence;
    }

    public function setRecurrence(?Recurrence $recurrence): static
    {
        $this->recurrence = $recurrence ?? Recurrence::Monthly;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): static
    {
        $this->amount = $amount ?? 0;

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

    public function getCostPrice(): ?int
    {
        return $this->costPrice;
    }

    public function setCostPrice(?int $costPrice): static
    {
        $this->costPrice = $costPrice;

        return $this;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function setSupplier(?string $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getNextRenewalDate(): ?\DateTimeImmutable
    {
        return $this->nextRenewalDate;
    }

    public function setNextRenewalDate(?\DateTimeImmutable $nextRenewalDate): static
    {
        $this->nextRenewalDate = $nextRenewalDate;

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

    public function getQuoteLine(): ?QuoteLine
    {
        return $this->quoteLine;
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
}
