<?php

namespace App\Repository;

use App\Entity\Project;
use App\Enum\ProjectKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /** @return list<Project> */
    public function findPublished(ProjectKind $kind): array
    {
        return $this->findBy(['published' => true, 'kind' => $kind], ['position' => 'ASC', 'id' => 'ASC']);
    }
}
