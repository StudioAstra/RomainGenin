<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ProjectKind: string implements TranslatableInterface
{
    case Pro = 'pro';
    case Perso = 'perso';

    public function label(): string
    {
        return match ($this) {
            self::Pro => 'Projet pro',
            self::Perso => 'Projet perso',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->label(), locale: $locale);
    }
}
