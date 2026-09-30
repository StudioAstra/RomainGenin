<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

/** @extends AbstractCrudController<Project> */
class ProjectCrudController extends AbstractCrudController
{
    public const UPLOAD_DIR = 'public/uploads/images';
    public const UPLOAD_BASE_PATH = 'uploads/images';

    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Projet')
            ->setEntityLabelInPlural('Projets')
            ->setDefaultSort(['kind' => 'ASC', 'position' => 'ASC'])
            ->setSearchFields(['title', 'client', 'description']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('kind')->add('published');
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('image', 'Visuel')
            ->setBasePath(self::UPLOAD_BASE_PATH)
            ->setUploadDir(self::UPLOAD_DIR)
            ->setUploadedFileNamePattern('[slug]-[randomhash].[extension]')
            ->mimeTypes('image/jpeg,image/png,image/webp,image/avif,image/gif')
            ->maxSize('8M')
            ->setHelp('Format 16:10 conseillé.');
        yield ChoiceField::new('kind', 'Type')
            ->renderAsBadges(['pro' => 'primary', 'perso' => 'secondary']);
        yield TextField::new('title', 'Nom du projet');
        yield TextField::new('client', 'Client · Agence');
        yield TextareaField::new('description', 'Rôle / réalisation')->hideOnIndex();
        yield ArrayField::new('technologies', 'Technos')->setRequired(false)->hideOnIndex();
        yield UrlField::new('url', 'Lien')->hideOnIndex();
        yield IntegerField::new('position', 'Ordre')->setHelp('Les plus petits en premier.');
        yield BooleanField::new('published', 'Publié')->renderAsSwitch();
    }
}
