<?php

namespace App\Command;

use App\Repository\ProfileRepository;
use App\Repository\ProjectRepository;
use App\Service\ImageOptimizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Optimise les images déjà importées (lancée à chaque démarrage du conteneur,
 * sans effet sur celles qui sont déjà optimisées).
 */
#[AsCommand(name: 'app:images:optimize', description: 'Convertit en WebP et allège les images déjà importées')]
class OptimizeImagesCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProfileRepository $profiles,
        private readonly ProjectRepository $projects,
        private readonly ImageOptimizer $optimizer,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $changed = 0;

        foreach ($this->profiles->findAll() as $profile) {
            if (null !== $photo = $profile->getPhoto()) {
                $optimized = $this->optimizer->optimize($photo, ImageOptimizer::PROFILE_PHOTO_MAX);
                if ($optimized !== $photo) {
                    $profile->setPhoto($optimized);
                    ++$changed;
                }
            }
        }

        foreach ($this->projects->findAll() as $project) {
            if (null !== $image = $project->getImage()) {
                $optimized = $this->optimizer->optimize($image, ImageOptimizer::PROJECT_IMAGE_MAX);
                if ($optimized !== $image) {
                    $project->setImage($optimized);
                    ++$changed;
                }
            }
        }

        $this->em->flush();

        $io->success(0 === $changed ? 'Toutes les images sont déjà optimisées.' : \sprintf('%d image(s) optimisée(s).', $changed));

        return Command::SUCCESS;
    }
}
