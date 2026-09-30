<?php

namespace App\Tests\Controller;

use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectKind;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        foreach ([Project::class, Profile::class, User::class] as $class) {
            $this->em->createQuery('DELETE FROM '.$class)->execute();
        }
    }

    public function testHomePageShowsProfileAndPublishedProjectsOnly(): void
    {
        $this->createProfile();
        $this->em->persist((new Project())->setKind(ProjectKind::Pro)->setTitle('Projet visible')->setPosition(20));
        $this->em->persist((new Project())->setKind(ProjectKind::Pro)->setTitle('Premier projet')->setPosition(10));
        $this->em->persist((new Project())->setKind(ProjectKind::Pro)->setTitle('Brouillon')->setPublished(false));
        $this->em->flush();

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Romain');
        self::assertSelectorTextContains('#projets-pro', 'Liste non exhaustive');
        self::assertSame(['Premier projet', 'Projet visible'], $crawler->filter('#projets-pro h3')->each(fn ($h) => trim($h->text())));
        self::assertSelectorTextContains('header#top', 'Disponible');
        self::assertSelectorTextNotContains('header#top', 'missions');
        self::assertStringContainsString('subject=', $crawler->filter('#contact a[href^="mailto:"]')->attr('href'));
    }

    public function testMaintenancePageForVisitorsWithCvAndContact(): void
    {
        $this->createProfile(maintenance: true);

        $crawler = $this->client->request('GET', '/');

        self::assertResponseStatusCodeSame(503);
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex');
        self::assertSelectorNotExists('#experiences');
        self::assertSame('/uploads/cv/cv-test.pdf', $crawler->filter('a[download]')->attr('href'));
        self::assertSame(
            'mailto:romain@example.com?subject=Prise%20de%20contact%20via%20localhost',
            $crawler->filter('a[href^="mailto:"]')->attr('href'),
        );
    }

    public function testAdminStillSeesTheSiteDuringMaintenance(): void
    {
        $this->createProfile(maintenance: true);
        $admin = (new User('admin@example.com'))->setRoles(['ROLE_ADMIN'])->setPassword('x');
        $this->em->persist($admin);
        $this->em->flush();

        $this->client->loginUser($admin);
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Mode maintenance actif');
    }

    public function testReorderRequiresValidCsrfToken(): void
    {
        $admin = (new User('admin@example.com'))->setRoles(['ROLE_ADMIN'])->setPassword('x');
        $this->em->persist($admin);
        $this->em->flush();
        $this->client->loginUser($admin);

        $this->client->jsonRequest('POST', '/admin/project/reorder', ['ids' => [1], '_token' => 'invalide']);

        self::assertResponseStatusCodeSame(403);
    }

    private function createProfile(bool $maintenance = false): void
    {
        $this->em->persist((new Profile())
            ->setFirstName('Romain')
            ->setLastName('Genin')
            ->setJobTitle('Software Engineer Front-End')
            ->setEmail('romain@example.com')
            ->setCvFile('cv-test.pdf')
            ->setMaintenance($maintenance));
        $this->em->flush();
    }
}
