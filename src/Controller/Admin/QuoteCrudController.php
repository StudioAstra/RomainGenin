<?php

namespace App\Controller\Admin;

use App\Entity\Quote;
use App\Entity\Subscription;
use App\Enum\QuoteStatus;
use App\Repository\SubscriptionRepository;
use App\Service\QuoteManager;
use App\Service\QuotePdfRenderer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @extends AbstractCrudController<Quote> */
class QuoteCrudController extends AbstractCrudController
{
    private const DUPLICATE_TOKEN = 'quote-duplicate';
    private const SUBSCRIPTIONS_TOKEN = 'quote-subscriptions';

    public static function getEntityFqcn(): string
    {
        return Quote::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Devis')
            ->setEntityLabelInPlural('Devis')
            ->setDefaultSort(['issueDate' => 'DESC', 'number' => 'DESC'])
            ->setSearchFields(['number', 'title', 'client.name'])
            ->setHelp(Crud::PAGE_NEW, 'Le numéro (DEV-année-xxx), la date de validité et les conditions sont remplis automatiquement à l\'enregistrement.');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('status')->add('client')->add('issueDate');
    }

    public function configureActions(Actions $actions): Actions
    {
        $pdf = Action::new('pdf', 'PDF', 'fa fa-file-pdf')
            ->linkToCrudAction('pdf')
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayIf(static fn (Quote $quote) => null !== $quote->getId());

        $duplicate = Action::new('duplicate', 'Dupliquer', 'fa fa-copy')
            ->linkToUrl(fn (Quote $quote) => $this->generateUrl('admin_quote_duplicate', [
                'id' => $quote->getId(),
                'token' => $this->container->get('security.csrf.token_manager')->getToken(self::DUPLICATE_TOKEN.$quote->getId())->getValue(),
            ]));

        $subscriptions = Action::new('createSubscriptions', 'Créer les abonnements', 'fa fa-rotate')
            ->linkToUrl(fn (Quote $quote) => $this->generateUrl('admin_quote_subscriptions', [
                'id' => $quote->getId(),
                'token' => $this->container->get('security.csrf.token_manager')->getToken(self::SUBSCRIPTIONS_TOKEN.$quote->getId())->getValue(),
            ]))
            ->displayIf(static fn (Quote $quote) => QuoteStatus::Accepted === $quote->getStatus() && [] !== $quote->getRecurringLines());

        return $actions
            ->add(Crud::PAGE_DETAIL, $subscriptions)
            ->add(Crud::PAGE_EDIT, $subscriptions)
            ->add(Crud::PAGE_INDEX, $pdf)
            ->add(Crud::PAGE_INDEX, $duplicate)
            ->add(Crud::PAGE_EDIT, $pdf)
            ->add(Crud::PAGE_EDIT, $duplicate)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $pdf)
            ->add(Crud::PAGE_DETAIL, $duplicate);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Devis');
        yield TextField::new('number', 'N°')->hideOnForm();
        yield TextField::new('title', 'Objet')->setColumns(8);
        yield AssociationField::new('client', 'Client')
            ->setHelp('Créez d\'abord le client dans « Clients ».')
            ->setColumns(4);
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(QuoteStatus::cases())
            ->renderAsNativeWidget()
            ->renderAsBadges(['brouillon' => 'secondary', 'envoye' => 'info', 'accepte' => 'success', 'refuse' => 'danger'])
            ->setColumns(4);
        yield DateField::new('issueDate', 'Date')->setColumns(4);
        yield DateField::new('validUntil', 'Valable jusqu\'au')->setHelp('Vide : selon « Mon entreprise ».')->setColumns(4)->hideOnIndex();
        yield TextareaField::new('introduction', 'Introduction')->setNumOfRows(2)->hideOnIndex();

        yield FormField::addFieldset('Lignes');
        yield CollectionField::new('lines', 'Lignes')
            ->useEntryCrudForm(QuoteLineCrudController::class)
            ->setEntryIsComplex()
            ->renderExpanded()
            ->allowAdd()
            ->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->onlyOnForms();
        yield MoneyField::new('totalExclTax', 'Total HT')->setCurrency('EUR')->setStoredAsCents()->hideOnForm();
        yield MoneyField::new('totalInclTax', 'Total TTC')->setCurrency('EUR')->setStoredAsCents()->onlyOnDetail();

        yield FormField::addFieldset('Paiement');
        yield CollectionField::new('installments', 'Échéancier')
            ->useEntryCrudForm(QuoteInstallmentCrudController::class)
            ->setEntryIsComplex()
            ->renderExpanded()
            ->allowAdd()
            ->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Vide : paiement en une fois. Sinon, les parts doivent totaliser 100 % (ex. Acompte 30 % à la signature, Solde 70 % à la livraison). Les abonnements sont facturés à part.')
            ->onlyOnForms();

        yield FormField::addFieldset('Conditions');
        yield TextareaField::new('conditions', 'Conditions')
            ->setHelp('Vide : conditions par défaut de « Mon entreprise ».')
            ->setNumOfRows(4)
            ->hideOnIndex();
    }

    #[AdminRoute('/{id}/pdf', name: 'pdf', options: ['methods' => ['GET']])]
    public function pdf(#[MapEntity(id: 'id')] Quote $quote, QuotePdfRenderer $renderer): Response
    {
        return new Response($renderer->render($quote), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $renderer->filename($quote),
                \sprintf('devis-%s.pdf', $quote->getNumber() ?? 'brouillon'),
            ),
        ]);
    }

    /** Crée un abonnement par ligne récurrente d'un devis accepté (sans doublon). */
    #[AdminRoute('/{id}/subscriptions', name: 'subscriptions', options: ['methods' => ['GET']])]
    public function createSubscriptions(#[MapEntity(id: 'id')] Quote $quote, Request $request, SubscriptionRepository $subscriptions, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::SUBSCRIPTIONS_TOKEN.$quote->getId(), (string) $request->query->get('token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $existing = $subscriptions->findQuoteLineIds($quote);
        $created = 0;
        foreach ($quote->getRecurringLines() as $line) {
            if (!\in_array($line->getId(), $existing, true)) {
                $em->persist(Subscription::fromQuoteLine($line, new \DateTimeImmutable('today')));
                ++$created;
            }
        }
        $em->flush();

        $this->addFlash(0 === $created ? 'info' : 'success', 0 === $created
            ? 'Les abonnements de ce devis existent déjà.'
            : \sprintf('%d abonnement(s) créé(s).', $created));

        return $this->redirectToRoute('admin_subscription_index');
    }

    #[AdminRoute('/{id}/duplicate', name: 'duplicate', options: ['methods' => ['GET']])]
    public function duplicate(#[MapEntity(id: 'id')] Quote $quote, Request $request, QuoteManager $manager, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::DUPLICATE_TOKEN.$quote->getId(), (string) $request->query->get('token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $copy = $quote->duplicate();
        $manager->prepare($copy);
        $em->persist($copy);
        $em->flush();

        $this->addFlash('success', \sprintf('Devis dupliqué : %s.', $copy->getNumber()));

        return $this->redirectToRoute('admin_quote_edit', ['entityId' => $copy->getId()]);
    }
}
