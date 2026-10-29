<?php

namespace App\Service;

use App\Entity\Quote;
use App\Repository\CompanyRepository;
use App\Repository\QuoteRepository;

/**
 * Prépare un devis avant enregistrement : numéro, validité, conditions et lignes.
 */
class QuoteManager
{
    public const NUMBER_PREFIX = 'DEV';

    public function __construct(
        private readonly QuoteRepository $quotes,
        private readonly CompanyRepository $companies,
    ) {
    }

    public function prepare(Quote $quote): void
    {
        $company = $this->companies->findCurrent();
        $issueDate = $quote->getIssueDate() ?? new \DateTimeImmutable('today');
        $quote->setIssueDate($issueDate);

        if (null === $quote->getValidUntil()) {
            $quote->setValidUntil($issueDate->modify(\sprintf('+%d days', $company?->getQuoteValidityDays() ?? 30)));
        }

        if (null === $quote->getConditions() && null !== $company) {
            $quote->setConditions($company->getQuoteConditions());
        }

        foreach (array_values($quote->getLines()->toArray()) as $i => $line) {
            $line->fillFromProduct();
            $line->setPosition(($i + 1) * 10);
        }

        if (null === $quote->getNumber()) {
            $quote->setNumber($this->nextNumber($issueDate));
        }
    }

    /** DEV-2026-001, DEV-2026-002… La numérotation repart à 1 chaque année. */
    public function nextNumber(\DateTimeImmutable $date): string
    {
        $prefix = \sprintf('%s-%s-', self::NUMBER_PREFIX, $date->format('Y'));
        $last = $this->quotes->findLastNumber($prefix);
        $next = null === $last ? 1 : (int) substr($last, \strlen($prefix)) + 1;

        return $prefix.str_pad((string) $next, 3, '0', \STR_PAD_LEFT);
    }
}
