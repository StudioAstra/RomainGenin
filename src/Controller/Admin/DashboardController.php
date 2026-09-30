<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\ProfileRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Theme;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly ProfileRepository $profiles,
    ) {
    }

    public function index(): Response
    {
        $profile = $this->profiles->findCurrent();

        if (null === $profile) {
            return $this->redirectToRoute('admin_profile_new');
        }

        return $this->redirectToRoute('admin_profile_edit', ['entityId' => $profile->getId()]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Romain Genin · Admin')
            ->setLocales(['fr'])
            ->setTheme(Theme::new()->primaryColor('oklch(0.5 0.11 155)', dark: 'oklch(0.68 0.12 155)'));
    }

    public function configureMenuItems(): iterable
    {
        $profile = $this->profiles->findCurrent();
        $profileItem = MenuItem::linkTo(ProfileCrudController::class, 'Profil, poste & photo', 'fa fa-user');
        if (null !== $profile) {
            $profileItem->setAction(Action::EDIT)->setEntityId($profile->getId());
        } else {
            $profileItem->setAction(Action::NEW);
        }

        if ($profile?->isMaintenance()) {
            $profileItem->setBadge('Maintenance', 'warning');
        }

        yield $profileItem;

        yield MenuItem::section('Contenu');
        yield MenuItem::linkTo(ProjectCrudController::class, 'Projets', 'fa fa-folder-open');
        yield MenuItem::linkTo(ExperienceCrudController::class, 'Expériences', 'fa fa-briefcase');
        yield MenuItem::linkTo(CertificationCrudController::class, 'Certifications', 'fa fa-certificate');
        yield MenuItem::linkTo(EducationCrudController::class, 'Formation', 'fa fa-graduation-cap');

        yield MenuItem::section();
        yield MenuItem::linkToRoute('Voir le site', 'fa fa-arrow-up-right-from-square', 'home')->setLinkTarget('_blank');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setName($user instanceof User ? $user->getEmail() : $user->getUserIdentifier());
    }
}
