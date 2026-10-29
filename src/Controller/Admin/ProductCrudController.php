<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Product> */
class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit / prestation')
            ->setEntityLabelInPlural('Produits & prestations')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'description']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom');
        yield TextareaField::new('description', 'Description')
            ->setHelp('Reprise sous le nom dans les lignes de devis.')
            ->setNumOfRows(3)
            ->hideOnIndex();
        yield TextField::new('unit', 'Unité')->setHelp('forfait, jour, heure, page…')->setColumns(3);
        yield MoneyField::new('unitPrice', 'Prix unitaire HT')->setCurrency('EUR')->setStoredAsCents()->setColumns(3);
        yield NumberField::new('vatRate', 'TVA (%)')->setStoredAsString()->setNumDecimals(2)->setHelp('0 en franchise de TVA.')->setColumns(3);
        yield BooleanField::new('active', 'Actif')->setHelp('Inactif : n\'est plus proposé dans les devis.')->renderAsSwitch();
    }
}
