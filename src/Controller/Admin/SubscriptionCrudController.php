<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use App\Enum\Recurrence;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/** @extends AbstractCrudController<Subscription> */
class SubscriptionCrudController extends AbstractCrudController
{
    public const RENEW_TOKEN = 'subscription-renew';

    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Abonnement')
            ->setEntityLabelInPlural('Abonnements')
            ->setDefaultSort(['active' => 'DESC', 'nextRenewalDate' => 'ASC'])
            ->setSearchFields(['label', 'client.name', 'supplier'])
            ->setHelp(Crud::PAGE_INDEX, 'Hébergements, noms de domaine, maintenance… Cliquez « Renouvelé » une fois la période facturée pour passer à l\'échéance suivante.');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('client')->add('recurrence')->add('active')->add('nextRenewalDate');
    }

    public function configureActions(Actions $actions): Actions
    {
        $renew = Action::new('renew', 'Renouvelé', 'fa fa-rotate')
            ->linkToUrl(fn (Subscription $s) => $this->generateUrl('admin_subscription_renew', [
                'id' => $s->getId(),
                'token' => $this->container->get('security.csrf.token_manager')->getToken(self::RENEW_TOKEN.$s->getId())->getValue(),
            ]))
            ->displayIf(static fn (Subscription $s) => $s->isActive());

        return $actions
            ->add(Crud::PAGE_INDEX, $renew)
            ->add(Crud::PAGE_DETAIL, $renew)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Abonnement');
        yield AssociationField::new('client', 'Client')->setColumns(6);
        yield TextField::new('label', 'Libellé')->setHelp('Ex. Hébergement VPS, Domaine exemple.fr.')->setColumns(6);
        yield ChoiceField::new('recurrence', 'Périodicité')
            ->setChoices(Recurrence::recurring())
            ->renderAsNativeWidget()
            ->renderAsBadges(['mensuel' => 'info', 'trimestriel' => 'info', 'annuel' => 'primary'])
            ->setColumns(3);
        yield MoneyField::new('amount', 'Prix HT / période')->setCurrency('EUR')->setStoredAsCents()->setColumns(3);
        yield NumberField::new('vatRate', 'TVA (%)')->setStoredAsString()->setNumDecimals(2)->setColumns(3)->hideOnIndex();
        yield DateField::new('nextRenewalDate', 'Prochain renouvellement')->setColumns(3);
        yield TextField::new('renewalStatus', 'Échéance')
            ->onlyOnIndex()
            ->setSortable(false)
            ->renderAsHtml()
            ->formatValue(static fn ($value, ?Subscription $s) => null === $s ? '' : self::renewalBadge($s));
        yield DateField::new('startDate', 'Début')->setColumns(3)->hideOnIndex();
        yield BooleanField::new('active', 'Actif')->renderAsSwitch()->setHelp('Désactivez un abonnement résilié.')->setColumns(3);

        yield FormField::addFieldset('Fournisseur & marge')->hideOnIndex();
        yield TextField::new('supplier', 'Fournisseur')->setHelp('Ex. OVHcloud, Gandi, o2switch.')->setColumns(4)->hideOnIndex();
        yield MoneyField::new('costPrice', 'Coût HT / période')->setCurrency('EUR')->setStoredAsCents()->setColumns(4)->hideOnIndex();
        yield MoneyField::new('margin', 'Marge / période')->setCurrency('EUR')->setStoredAsCents()->onlyOnDetail();
        yield MoneyField::new('yearlyAmount', 'CA annuel HT')->setCurrency('EUR')->setStoredAsCents()->onlyOnDetail();
        yield TextareaField::new('notes', 'Notes')->setNumOfRows(3)->hideOnIndex();
    }

    #[AdminRoute('/{id}/renew', name: 'renew', options: ['methods' => ['GET']])]
    public function renew(#[MapEntity(id: 'id')] Subscription $subscription, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::RENEW_TOKEN.$subscription->getId(), (string) $request->query->get('token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $subscription->renew();
        $em->flush();

        $this->addFlash('success', \sprintf('« %s » renouvelé jusqu\'au %s.', $subscription->getLabel(), $subscription->getNextRenewalDate()?->format('d/m/Y')));

        return $this->redirectToRoute('admin_subscription_index');
    }

    private static function renewalBadge(Subscription $subscription): string
    {
        $days = $subscription->getDaysUntilRenewal() ?? 0;
        $type = match (true) {
            !$subscription->isActive() => 'secondary',
            $days <= 0 => 'danger',
            $days <= 30 => 'warning',
            default => 'success',
        };

        return \sprintf('<span class="badge badge-%s">%s</span>', $type, htmlspecialchars($subscription->getRenewalStatus()));
    }
}
