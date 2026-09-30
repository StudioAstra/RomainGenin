<?php

namespace App\Controller\Admin;

use App\Entity\Profile;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

/** @extends AbstractCrudController<Profile> */
class ProfileCrudController extends AbstractCrudController
{
    public const CV_UPLOAD_DIR = 'public/uploads/cv';
    public const CV_BASE_PATH = 'uploads/cv';

    public static function getEntityFqcn(): string
    {
        return Profile::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Profil')
            ->setEntityLabelInPlural('Profil')
            ->setPageTitle(Crud::PAGE_EDIT, 'Profil, poste & photo')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer le profil');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Une seule fiche profil : on l'édite, on ne la supprime pas.
        return $actions
            ->disable(Action::DELETE)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Identité & poste');
        yield TextField::new('firstName', 'Prénom')->setColumns(6);
        yield TextField::new('lastName', 'Nom')->setColumns(6);
        yield TextField::new('jobTitle', 'Poste occupé')
            ->setHelp('Affiché en vert sous le nom, ex. « Software Engineer Front-End ».')
            ->setColumns(6);
        yield TextField::new('jobSubtitle', 'Sous-titre du poste')
            ->setHelp('Seconde ligne, ex. « Adobe Commerce / Symfony ».')
            ->setColumns(6);
        yield TextField::new('location', 'Localisation')->setColumns(6);
        yield ImageField::new('photo', 'Photo')
            ->setBasePath(ProjectCrudController::UPLOAD_BASE_PATH)
            ->setUploadDir(ProjectCrudController::UPLOAD_DIR)
            ->setUploadedFileNamePattern('photo-[randomhash].[extension]')
            ->mimeTypes('image/jpeg,image/png,image/webp,image/avif')
            ->maxSize('8M')
            ->setHelp('Format portrait 4:5 conseillé.')
            ->setColumns(6);

        yield FormField::addFieldset('Introduction');
        yield TextareaField::new('intro', 'Texte d\'introduction')->setNumOfRows(5)->hideOnIndex();

        yield FormField::addFieldset('Compétences & loisirs');
        yield ArrayField::new('skills', 'Compétences')->setRequired(false)->setColumns(6)->hideOnIndex();
        yield ArrayField::new('hobbies', 'Loisirs')->setRequired(false)->setColumns(6)->hideOnIndex();

        yield FormField::addFieldset('Contact');
        yield EmailField::new('email', 'E-mail')->setColumns(6);
        yield TelephoneField::new('phone', 'Téléphone')->setColumns(6)->hideOnIndex();
        yield UrlField::new('linkedinUrl', 'LinkedIn')->setColumns(6)->hideOnIndex();
        yield UrlField::new('maltUrl', 'Malt')->setColumns(6)->hideOnIndex();
        yield BooleanField::new('showMalt', 'Afficher le lien Malt')
            ->setHelp('Dans le menu et le bloc contact.')
            ->renderAsSwitch()
            ->setColumns(6)
            ->hideOnIndex();

        yield FormField::addFieldset('CV');
        yield FileField::new('cvFile', 'CV (PDF)')
            ->setBasePath(self::CV_BASE_PATH)
            ->setUploadDir(self::CV_UPLOAD_DIR)
            ->setUploadedFileNamePattern('cv-[randomhash].[extension]')
            ->mimeTypes('application/pdf', 'Le CV doit être un fichier PDF.')
            ->maxSize('10M')
            ->deleteReplacedFile()
            ->setHelp('Bouton « CV » dans le menu et le bloc contact. Sans fichier, les boutons sont masqués.')
            ->hideOnIndex();
    }
}
