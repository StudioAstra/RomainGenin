<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ProjectKind: string implements TranslatableInterface
{
    /** Projet réalisé en agence */
    case Pro = 'pro';
    /** Projet réalisé en indépendant (auto-entrepreneur) */
    case Freelance = 'freelance';
    /** Projet personnel */
    case Perso = 'perso';

    public function label(): string
    {
        return match ($this) {
            self::Pro => 'Pro (agence)',
            self::Freelance => 'Freelance',
            self::Perso => 'Perso',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->label(), locale: $locale);
    }
}
