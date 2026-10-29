<?php

namespace App\Controller\Admin;

use App\Entity\QuoteInstallment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Formulaire d'une échéance, intégré au formulaire du devis (pas de menu dédié).
 *
 * @extends AbstractCrudController<QuoteInstallment>
 */
class QuoteInstallmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return QuoteInstallment::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::INDEX, Action::NEW, Action::EDIT, Action::DETAIL, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('label', 'Échéance')->setHelp('Ex. Acompte, 2e versement, Solde.')->setColumns(6);
        yield NumberField::new('percent', 'Part (%)')->setStoredAsString()->setNumDecimals(2)->setColumns(6);
        yield TextField::new('dueText', 'Quand')->setHelp('Ex. à la signature, à la livraison.')->setColumns(6);
        yield DateField::new('dueDate', 'Date (optionnel)')->setColumns(6);
    }
}
