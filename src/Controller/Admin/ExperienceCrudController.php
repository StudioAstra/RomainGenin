<?php

namespace App\Controller\Admin;

use App\Entity\Experience;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Experience> */
class ExperienceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Experience::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Expérience')
            ->setEntityLabelInPlural('Expériences')
            ->setDefaultSort(['position' => 'ASC', 'startDate' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Poste');
        yield TextField::new('company', 'Entreprise · précisions')
            ->setHelp('Ex. « Agence Dn\'D · Adobe Commerce / Hyvä, Symfony & Shopify ».');
        yield DateField::new('startDate', 'Début')->setFormat('MM.yyyy')->setColumns(3);
        yield DateField::new('endDate', 'Fin')
            ->setFormat('MM.yyyy')
            ->setHelp('Laisser vide pour le poste actuel (« auj. »).')
            ->setColumns(3);
        yield TextareaField::new('description', 'Description')->setNumOfRows(4)->hideOnIndex();
        yield IntegerField::new('position', 'Ordre')->setHelp('Les plus petits en premier.');
        yield BooleanField::new('published', 'Publié')->renderAsSwitch();
    }
}
