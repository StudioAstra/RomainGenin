<?php

namespace App\Tests\Controller;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Quote;
use App\Entity\QuoteInstallment;
use App\Entity\QuoteLine;
use App\Entity\Subscription;
use App\Enum\QuoteStatus;
use App\Enum\Recurrence;
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
        foreach ([Subscription::class, QuoteInstallment::class, QuoteLine::class, Quote::class, Client::class, Company::class, User::class] as $class) {
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

    public function testInstallmentsMustTotalOneHundredPercent(): void
    {
        $validator = static::getContainer()->get('validator');
        $quote = $this->createQuote('DEV-2026-001')
            ->addInstallment((new QuoteInstallment())->setLabel('Acompte')->setPercent('30'))
            ->addInstallment((new QuoteInstallment())->setLabel('Solde')->setPercent('60'));

        $violations = $validator->validate($quote);
        self::assertCount(1, $violations);
        self::assertStringContainsString('100 %', (string) $violations->get(0)->getMessage());

        $quote->getInstallments()->last()->setPercent('70');
        self::assertCount(0, $validator->validate($quote));
    }

    public function testVatIsZeroedUnderVatExemption(): void
    {
        $this->em->persist(new Company()->setName('Romain Genin'));
        $this->em->flush();
        $quote = $this->createQuote('DEV-2026-001');

        static::getContainer()->get(QuoteManager::class)->prepare($quote);

        self::assertSame('0.00', $quote->getLines()->first()->getVatRate());
    }

    public function testAcceptedQuoteCreatesSubscriptionsOnce(): void
    {
        $quote = $this->createQuote('DEV-2026-001');
        $quote->setStatus(QuoteStatus::Accepted)
            ->addLine((new QuoteLine())->setDescription("Hébergement\nVPS")->setUnitPrice(1500)->setRecurrence(Recurrence::Monthly));
        $this->em->flush();

        $crawler = $this->client->request('GET', '/admin/quote/'.$quote->getId());
        $url = $crawler->filter('a.action-createSubscriptions')->attr('href');

        $this->client->request('GET', $url);
        self::assertResponseRedirects();
        $this->client->request('GET', $url);

        $subscriptions = $this->em->getRepository(Subscription::class)->findAll();
        self::assertCount(1, $subscriptions, 'pas de doublon au second clic');
        self::assertSame('Hébergement', $subscriptions[0]->getLabel());
        self::assertSame(1500, $subscriptions[0]->getAmount());
        self::assertSame((new \DateTimeImmutable('today +1 month'))->format('Y-m-d'), $subscriptions[0]->getNextRenewalDate()->format('Y-m-d'));
    }

    public function testRenewRequiresTokenAndAdvancesDate(): void
    {
        $client = (new Client())->setName('Client');
        $subscription = (new Subscription())->setClient($client)->setLabel('Domaine')->setRecurrence(Recurrence::Yearly)
            ->setNextRenewalDate(new \DateTimeImmutable('2026-11-15'));
        $this->em->persist($client);
        $this->em->persist($subscription);
        $this->em->flush();

        $this->client->request('GET', '/admin/subscription/'.$subscription->getId().'/renew?token=invalide');
        self::assertResponseStatusCodeSame(403);

        $crawler = $this->client->request('GET', '/admin/subscription');
        self::assertSelectorTextContains('body', 'Dans');
        $this->client->request('GET', $crawler->filter('a.action-renew')->attr('href'));
        self::assertResponseRedirects();

        $this->em->clear();
        self::assertSame('2027-11-15', $this->em->find(Subscription::class, $subscription->getId())->getNextRenewalDate()->format('Y-m-d'));
    }
}
