<?php

namespace App\Repository;

use App\Entity\Quote;
use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /** Abonnements actifs à renouveler avant une date (retards compris). */
    public function countDueBefore(\DateTimeImmutable $date): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.active = true')
            ->andWhere('s.nextRenewalDate <= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<int> Ids des lignes de devis déjà converties en abonnement. */
    public function findQuoteLineIds(Quote $quote): array
    {
        return array_map('intval', array_column($this->createQueryBuilder('s')
            ->select('IDENTITY(s.quoteLine) AS id')
            ->join('s.quoteLine', 'l')
            ->where('l.quote = :quote')
            ->setParameter('quote', $quote)
            ->getQuery()
            ->getScalarResult(), 'id'));
    }
}
