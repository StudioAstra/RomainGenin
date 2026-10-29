<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Client> */
class ClientCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Client::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client')
            ->setEntityLabelInPlural('Clients')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'contactName', 'email']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom / raison sociale')->setColumns(6);
        yield TextField::new('contactName', 'Contact')->setColumns(6);
        yield EmailField::new('email', 'E-mail')->setColumns(6);
        yield TelephoneField::new('phone', 'Téléphone')->setColumns(6);
        yield TextareaField::new('address', 'Adresse')->setNumOfRows(3)->setColumns(6)->hideOnIndex();
        yield TextField::new('siret', 'SIRET')->setColumns(6)->hideOnIndex();
        yield TextareaField::new('notes', 'Notes internes')->setNumOfRows(3)->hideOnIndex();
    }
}
