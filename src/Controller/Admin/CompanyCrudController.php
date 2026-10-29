<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Repository\ProfileRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Company> */
class CompanyCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ProfileRepository $profiles,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Company::class;
    }

    /** Préremplit l'émetteur avec les coordonnées du profil du site. */
    public function createEntity(string $entityFqcn): Company
    {
        $company = new Company();
        if (null !== $profile = $this->profiles->findCurrent()) {
            $company
                ->setName($profile->getFullName())
                ->setEmail($profile->getEmail())
                ->setPhone($profile->getPhone())
                ->setWebsite($this->container->get('request_stack')->getCurrentRequest()?->getSchemeAndHttpHost());
        }

        return $company;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Mon entreprise')
            ->setEntityLabelInPlural('Mon entreprise')
            ->setPageTitle(Crud::PAGE_EDIT, 'Mon entreprise (émetteur des devis)')
            ->setPageTitle(Crud::PAGE_NEW, 'Mon entreprise (émetteur des devis)');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DELETE)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Identité');
        yield TextField::new('name', 'Nom / raison sociale')->setColumns(6);
        yield TextField::new('legalForm', 'Forme juridique')->setHelp('Ex. « Entrepreneur individuel ».')->setColumns(6);
        yield TextField::new('siret', 'SIRET')->setColumns(6);
        yield TextareaField::new('address', 'Adresse')->setNumOfRows(3)->setColumns(6)->hideOnIndex();
        yield EmailField::new('email', 'E-mail')->setColumns(6);
        yield TelephoneField::new('phone', 'Téléphone')->setColumns(6)->hideOnIndex();
        yield TextField::new('website', 'Site web')->setColumns(6)->hideOnIndex();

        yield FormField::addFieldset('TVA & paiement');
        yield BooleanField::new('vatExempt', 'Franchise en base de TVA')
            ->setHelp('Auto-entrepreneur : affiche « TVA non applicable, art. 293 B du CGI » et masque la TVA sur le devis.')
            ->renderAsSwitch()
            ->setColumns(6);
        yield TextField::new('vatNumber', 'N° de TVA intracommunautaire')->setColumns(6)->hideOnIndex();
        yield TextField::new('iban', 'IBAN')->setColumns(6)->hideOnIndex();
        yield TextField::new('bic', 'BIC')->setColumns(6)->hideOnIndex();

        yield FormField::addFieldset('Devis');
        yield IntegerField::new('quoteValidityDays', 'Validité (jours)')->setColumns(3)->hideOnIndex();
        yield TextareaField::new('quoteConditions', 'Conditions par défaut')
            ->setHelp('Reprises sur chaque nouveau devis, modifiables devis par devis.')
            ->setNumOfRows(4)
            ->hideOnIndex();
    }
}
