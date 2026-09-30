<?php

namespace App\Controller\Admin;

use App\Entity\Education;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Education> */
class EducationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Education::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Formation')
            ->setEntityLabelInPlural('Formation')
            ->setDefaultSort(['position' => 'ASC', 'startYear' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Diplôme');
        yield TextField::new('school', 'Établissement');
        yield IntegerField::new('startYear', 'Début')->setColumns(3);
        yield IntegerField::new('endYear', 'Fin')->setColumns(3);
        yield IntegerField::new('position', 'Ordre');
    }
}
