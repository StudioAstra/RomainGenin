<?php

namespace App\Controller\Admin;

use App\Entity\QuoteLine;
use App\Enum\Recurrence;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Formulaire d'une ligne, intégré au formulaire du devis (pas de menu dédié).
 *
 * @extends AbstractCrudController<QuoteLine>
 */
class QuoteLineCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return QuoteLine::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        // Les lignes ne se gèrent que depuis le formulaire du devis
        return $actions->disable(Action::INDEX, Action::NEW, Action::EDIT, Action::DETAIL, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('product', 'Produit du catalogue')
            ->setQueryBuilder(fn (QueryBuilder $qb) => $qb->andWhere('entity.active = true')->orderBy('entity.name'))
            ->renderAsNativeWidget()
            ->setHelp('Optionnel : remplit les champs laissés vides à l\'enregistrement.')
            ->setColumns(12);
        yield TextareaField::new('description', 'Désignation')
            ->setHelp('1re ligne en gras sur le devis, les suivantes en détail.')
            ->setNumOfRows(2)
            ->setColumns(12);
        yield NumberField::new('quantity', 'Quantité')->setStoredAsString()->setNumDecimals(2)->setColumns(6);
        yield TextField::new('unit', 'Unité')->setColumns(6);
        yield MoneyField::new('unitPrice', 'Prix unitaire HT')->setCurrency('EUR')->setStoredAsCents()->setColumns(6);
        yield NumberField::new('vatRate', 'TVA (%)')->setStoredAsString()->setNumDecimals(2)->setColumns(6);
        yield ChoiceField::new('recurrence', 'Facturation')
            ->setChoices(Recurrence::cases())
            ->renderAsNativeWidget()
            ->setRequired(false)
            ->setFormTypeOption('placeholder', 'Selon le produit (ponctuel sinon)')
            ->setHelp('Abonnement : affiché à part sur le devis, hors total à payer.')
            ->setColumns(6);
    }
}
