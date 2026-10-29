<?php

namespace App\EventSubscriber;

use App\Entity\Quote;
use App\Service\QuoteManager;
use EasyCorp\Bundle\EasyAdminBundle\Event\AbstractLifecycleEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PrepareQuoteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly QuoteManager $manager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'prepare',
            BeforeEntityUpdatedEvent::class => 'prepare',
        ];
    }

    public function prepare(AbstractLifecycleEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if ($entity instanceof Quote) {
            $this->manager->prepare($entity);
        }
    }
}
