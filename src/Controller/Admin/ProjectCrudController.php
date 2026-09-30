<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Service\ImageOptimizer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @extends AbstractCrudController<Project> */
class ProjectCrudController extends AbstractCrudController
{
    public const UPLOAD_DIR = ImageOptimizer::UPLOAD_DIR;
    public const UPLOAD_BASE_PATH = 'uploads/images';
    private const REORDER_TOKEN = 'project-reorder';

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
            ->setSearchFields(['title', 'client', 'description'])
            // Tous les projets sur une page pour pouvoir les réordonner
            ->setPaginatorPageSize(200)
            ->setHelp(Crud::PAGE_INDEX, 'Glissez les lignes avec ⠿ pour changer l\'ordre d\'affichage sur le site.');
    }

    public function configureAssets(Assets $assets): Assets
    {
        $config = \sprintf(
            '<div id="project-reorder" hidden data-url="%s" data-token="%s"></div>',
            htmlspecialchars($this->generateUrl('admin_project_reorder')),
            htmlspecialchars($this->container->get('security.csrf.token_manager')->getToken(self::REORDER_TOKEN)->getValue()),
        );

        return parent::configureAssets($assets)
            ->addAssetMapperEntry('admin-project-sortable')
            ->addHtmlContentToBody($config);
    }

    /**
     * Enregistre l'ordre issu du glisser-déposer : reçoit les ids dans le nouvel ordre
     * et leur redistribue leurs positions actuelles (la liste peut être filtrée).
     */
    #[AdminRoute('/reorder', name: 'reorder', options: ['methods' => ['POST']])]
    public function reorder(Request $request, ProjectRepository $projects, EntityManagerInterface $em): JsonResponse
    {
        $payload = $request->toArray();

        if (!$this->isCsrfTokenValid(self::REORDER_TOKEN, (string) ($payload['_token'] ?? ''))) {
            return new JsonResponse(['error' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $ids = array_values(array_unique(array_map('intval', (array) ($payload['ids'] ?? []))));
        $byId = [];
        foreach ($projects->findBy(['id' => $ids]) as $project) {
            $byId[$project->getId()] = $project;
        }

        if (\count($byId) !== \count($ids)) {
            return new JsonResponse(['error' => 'Projet introuvable.'], Response::HTTP_BAD_REQUEST);
        }

        $slots = array_map(static fn (Project $p) => $p->getPosition(), $byId);
        sort($slots);
        // Positions en double (ex. tout à 0) : on repart d'une suite régulière
        if (\count(array_unique($slots)) !== \count($slots)) {
            $slots = array_map(static fn (int $i) => $slots[0] + ($i + 1) * 10, array_keys($slots));
        }

        $positions = [];
        foreach ($ids as $i => $id) {
            $byId[$id]->setPosition($slots[$i]);
            $positions[$id] = $slots[$i];
        }
        $em->flush();

        return new JsonResponse(['positions' => $positions]);
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
            ->maxSize('20M')
            ->deleteReplacedFile()
            ->setHelp('Format 16:10 conseillé. Converti automatiquement en WebP et redimensionné.');
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
