<?php

namespace App\Tests\Controller;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Quote;
use App\Entity\QuoteLine;
use App\Entity\User;
use App\Service\QuoteManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class QuoteAdminTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        foreach ([QuoteLine::class, Quote::class, Client::class, Company::class, User::class] as $class) {
            $this->em->createQuery('DELETE FROM '.$class)->execute();
        }

        $admin = (new User('admin@example.com'))->setRoles(['ROLE_ADMIN'])->setPassword('x');
        $this->em->persist($admin);
        $this->em->flush();
        $this->client->loginUser($admin);
    }

    public function testNumbersFollowEachOtherAndRestartEachYear(): void
    {
        $manager = static::getContainer()->get(QuoteManager::class);
        $this->createQuote('DEV-2026-007');

        self::assertSame('DEV-2026-008', $manager->nextNumber(new \DateTimeImmutable('2026-12-31')));
        self::assertSame('DEV-2027-001', $manager->nextNumber(new \DateTimeImmutable('2027-01-02')));
    }

    public function testPdfIsGeneratedInBothVatModes(): void
    {
        $company = (new Company())->setName('Romain Genin')->setSiret('12345678900012');
        $this->em->persist($company);
        $quote = $this->createQuote('DEV-2026-001');

        $this->client->request('GET', '/admin/quote/'.$quote->getId().'/pdf');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringStartsWith('%PDF-', $this->client->getResponse()->getContent());
        self::assertStringContainsString('filename=devis-DEV-2026-001.pdf', $this->client->getResponse()->headers->get('Content-Disposition'));

        $company->setVatExempt(false);
        $this->em->flush();
        $this->client->request('GET', '/admin/quote/'.$quote->getId().'/pdf');
        self::assertResponseIsSuccessful();
    }

    public function testDuplicateRequiresToken(): void
    {
        $quote = $this->createQuote('DEV-2026-001');

        $this->client->request('GET', '/admin/quote/'.$quote->getId().'/duplicate?token=invalide');

        self::assertResponseStatusCodeSame(403);
        self::assertSame(1, $this->em->getRepository(Quote::class)->count([]));
    }

    private function createQuote(string $number): Quote
    {
        $client = (new Client())->setName('Menuiserie Dupont');
        $quote = (new Quote())->setTitle('Site internet')->setClient($client)->setNumber($number)
            ->addLine((new QuoteLine())->setDescription("Site vitrine\n5 pages")->setUnitPrice(180000)->setVatRate('20'));
        $this->em->persist($client);
        $this->em->persist($quote);
        $this->em->flush();

        return $quote;
    }
}
