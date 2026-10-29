<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Recurrence: string implements TranslatableInterface
{
    case OneOff = 'ponctuel';
    case Monthly = 'mensuel';
    case Quarterly = 'trimestriel';
    case Yearly = 'annuel';

    /** @return list<self> Périodicités d'un abonnement (hors ponctuel). */
    public static function recurring(): array
    {
        return [self::Monthly, self::Quarterly, self::Yearly];
    }

    public function isRecurring(): bool
    {
        return self::OneOff !== $this;
    }

    public function label(): string
    {
        return match ($this) {
            self::OneOff => 'Ponctuel',
            self::Monthly => 'Mensuel',
            self::Quarterly => 'Trimestriel',
            self::Yearly => 'Annuel',
        };
    }

    /** Suffixe de prix, ex. « / mois ». */
    public function suffix(): string
    {
        return match ($this) {
            self::OneOff => '',
            self::Monthly => '/ mois',
            self::Quarterly => '/ trimestre',
            self::Yearly => '/ an',
        };
    }

    /** Pas pour DateTimeImmutable::modify(). */
    public function interval(): string
    {
        return match ($this) {
            self::OneOff => '+0 day',
            self::Monthly => '+1 month',
            self::Quarterly => '+3 months',
            self::Yearly => '+1 year',
        };
    }

    public function perYear(): int
    {
        return match ($this) {
            self::OneOff => 0,
            self::Monthly => 12,
            self::Quarterly => 4,
            self::Yearly => 1,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->label(), locale: $locale);
    }
}
