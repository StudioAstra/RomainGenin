<?php

namespace App\EventSubscriber;

use App\Entity\Profile;
use App\Entity\Project;
use App\Service\ImageOptimizer;
use EasyCorp\Bundle\EasyAdminBundle\Event\AbstractLifecycleEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EasyAdmin a déjà écrit le fichier sur le disque quand ces événements sont émis :
 * on l'optimise et on enregistre le nouveau nom (.webp) avant l'écriture en base.
 */
class OptimizeUploadedImagesSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ImageOptimizer $optimizer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'optimize',
            BeforeEntityUpdatedEvent::class => 'optimize',
        ];
    }

    public function optimize(AbstractLifecycleEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if ($entity instanceof Profile && null !== $entity->getPhoto()) {
            $entity->setPhoto($this->optimizer->optimize($entity->getPhoto(), ImageOptimizer::PROFILE_PHOTO_MAX));
        }

        if ($entity instanceof Project && null !== $entity->getImage()) {
            $entity->setImage($this->optimizer->optimize($entity->getImage(), ImageOptimizer::PROJECT_IMAGE_MAX));
        }
    }
}
