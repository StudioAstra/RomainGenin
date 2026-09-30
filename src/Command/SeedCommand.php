<?php

namespace App\Command;

use App\Controller\Admin\ProjectCrudController;
use App\Entity\Certification;
use App\Entity\Education;
use App\Entity\Experience;
use App\Entity\Profile;
use App\Entity\Project;
use App\Enum\ProjectKind;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Remplit la base avec le contenu de la maquette, uniquement si elle est vide.
 */
#[AsCommand(name: 'app:seed', description: 'Insère le contenu initial du site si aucun profil n\'existe')]
class SeedCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProfileRepository $profiles,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        if (null !== $this->profiles->findCurrent()) {
            $io->note('Contenu déjà présent, rien à faire.');

            return Command::SUCCESS;
        }

        $profile = (new Profile())
            ->setFirstName('Romain')
            ->setLastName('Genin')
            ->setJobTitle('Software Engineer Front-End')
            ->setJobSubtitle('Adobe Commerce / Symfony')
            ->setLocation('Bouzigues, Occitanie')
            ->setIntro('Software Engineer front-end depuis 6 ans, je suis spécialisé en e-commerce sur Adobe Commerce, Hyvä et Symfony. J\'ai également des compétences dans la conception graphique, développement d\'outils métiers et gestion de données. Du lead technique à la gestion de projet, j\'accompagne les équipes et les clients avec rigueur et qualités.')
            ->setEmail('romaingenin@icloud.com')
            ->setPhone('06 80 54 96 06')
            ->setLinkedinUrl('https://www.linkedin.com/in/romaingenin')
            ->setMaltUrl('https://www.malt.fr/profile/romaingenin')
            ->setSkills(['Front-end', 'Gestion de projet', 'Gestion de comptes', 'Adobe Commerce', 'Magento 2', 'Hyvä', 'Symfony', 'Shopify', 'Accessibilité'])
            ->setHobbies(['Plongée', 'Bateau', 'Mécanique', 'Menuiserie'])
            ->setPhoto($this->copySeedImage('photo.webp', 'photo-romain-genin.webp'));
        $this->em->persist($profile);

        $experiences = [
            ['Développeur web front-end', 'Agence Dn\'D · Adobe Commerce / Hyvä, Symfony & Shopify', '2024-10-01', null, 'Développement front sur Adobe Commerce / Hyvä et Symfony, accompagnement des clients dans leur communication et leur commerce en ligne, en équipe avec développeurs front, back et chefs de projet. Migration de sites e-commerces vers Shopify'],
            ['Senior Software Engineer', 'EPAM Systems / Emakina · Lead développement', '2020-09-01', '2024-09-01', 'Migration Magento 1 → Magento 2. Refonte d\'une codebase magento 2 avec cart / checkout et commande rapide, thèmes pour 18 pays, accessibilité. Coordination d\'équipe, suivi contractuel, KPI, reporting client.'],
            ['Mentor étudiant', 'OpenClassrooms', '2023-03-01', '2024-09-01', 'Accompagnement d\'étudiants en formation Intégrateur Web.'],
            ['Développement web / intégration', 'Goyave Lab · apprentissage', '2019-08-01', '2020-09-01', 'Outil métier Symfony : redesign, SMS via Twilio, webapp tablette hors ligne.'],
        ];
        foreach ($experiences as $i => [$title, $company, $start, $end, $description]) {
            $this->em->persist((new Experience())
                ->setTitle($title)
                ->setCompany($company)
                ->setStartDate(new \DateTimeImmutable($start))
                ->setEndDate($end ? new \DateTimeImmutable($end) : null)
                ->setDescription($description)
                ->setPosition(($i + 1) * 10));
        }

        $this->em->persist((new Certification())->setTitle('Adobe Certified Expert')->setSubtitle('Commerce Front-End Developer')->setFeatured(true)->setPosition(10));
        $this->em->persist((new Certification())->setTitle('Adobe Certified Professional')->setSubtitle('Commerce Front-End Developer')->setPosition(20));

        $this->em->persist((new Education())->setTitle('Licence pro Développeur d\'applications Web & Big Data')->setSchool('Université de Limoges')->setStartYear(2019)->setEndYear(2020)->setPosition(10));
        $this->em->persist((new Education())->setTitle('DUT Métiers du Multimédia et de l\'Internet')->setSchool('Université de Limoges')->setStartYear(2017)->setEndYear(2019)->setPosition(20));

        $this->em->persist((new Project())
            ->setKind(ProjectKind::Perso)
            ->setTitle('StudioAstra')
            ->setDescription('Communication digitale et outil de gestion tout-en-un pour entrepreneurs : devis, factures, paiements en ligne et site web intégré.')
            ->setUrl('https://getstudioastra.fr')
            ->setPosition(10));

        $this->em->flush();
        $io->success('Contenu initial inséré.');

        return Command::SUCCESS;
    }

    private function copySeedImage(string $source, string $target): ?string
    {
        $from = $this->projectDir.'/resources/seed/'.$source;
        if (!is_file($from)) {
            return null;
        }

        (new Filesystem())->copy($from, $this->projectDir.'/'.ProjectCrudController::UPLOAD_DIR.'/'.$target);

        return $target;
    }
}
