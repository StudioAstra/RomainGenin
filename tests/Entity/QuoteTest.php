<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Entity\QuoteInstallment;
use App\Entity\Subscription;
use App\Enum\Recurrence;
use App\Entity\Quote;
use App\Entity\QuoteLine;
use PHPUnit\Framework\TestCase;

class QuoteTest extends TestCase
{
    public function testTotalsWithSeveralVatRates(): void
    {
        $quote = (new Quote())
            ->addLine((new QuoteLine())->setDescription('Dév')->setQuantity('2.5')->setUnitPrice(40000)->setVatRate('20'))
            ->addLine((new QuoteLine())->setDescription('Livre')->setQuantity(1)->setUnitPrice(1999)->setVatRate('5.5'))
            ->addLine((new QuoteLine())->setDescription('Offert')->setQuantity(1)->setUnitPrice(0));

        self::assertSame(101999, $quote->getTotalExclTax());
        self::assertSame([
            '0.00' => ['base' => 0, 'vat' => 0],
            '5.50' => ['base' => 1999, 'vat' => 110],
            '20.00' => ['base' => 100000, 'vat' => 20000],
        ], $quote->getVatBreakdown());
        self::assertSame(122109, $quote->getTotalInclTax());
    }

    public function testLineIsCompletedFromProductWithoutOverwritingInput(): void
    {
        $product = (new Product())->setName('Site vitrine')->setDescription('5 pages')->setUnit('forfait')->setUnitPrice(180000)->setVatRate('20');
        $line = (new QuoteLine())->setProduct($product)->setUnitPrice(150000);

        $line->fillFromProduct();

        self::assertSame("Site vitrine\n5 pages", $line->getDescription());
        self::assertSame('forfait', $line->getUnit());
        self::assertSame(150000, $line->getUnitPrice(), 'le prix saisi est conservé');
        self::assertSame('20.00', $line->getVatRate());
    }

    public function testDuplicateIsADraftWithoutNumber(): void
    {
        $quote = (new Quote())->setTitle('Site')->setNumber('DEV-2026-004')
            ->addLine((new QuoteLine())->setDescription('A')->setUnitPrice(100));

        $copy = $quote->duplicate();

        self::assertNull($copy->getNumber());
        self::assertSame('Site', $copy->getTitle());
        self::assertCount(1, $copy->getLines());
        self::assertNotSame($quote->getLines()->first(), $copy->getLines()->first());
        self::assertSame($copy, $copy->getLines()->first()->getQuote());
    }

    public function testRecurringLinesAreKeptOutOfTheTotalToPay(): void
    {
        $hosting = (new Product())->setName('Hébergement')->setUnitPrice(1500)->setRecurrence(Recurrence::Monthly);
        $quote = (new Quote())
            ->addLine((new QuoteLine())->setDescription('Site')->setUnitPrice(180000))
            ->addLine((new QuoteLine())->setProduct($hosting)->setUnitPrice(1500))
            ->addLine((new QuoteLine())->setDescription('Domaine')->setUnitPrice(1200)->setRecurrence(Recurrence::Yearly));

        self::assertSame(180000, $quote->getTotalExclTax());
        self::assertCount(1, $quote->getOneOffLines());
        $totals = $quote->getRecurringTotals();
        self::assertSame(['mensuel', 'annuel'], array_keys($totals));
        self::assertSame(1500, $totals['mensuel']['exclTax']);
        self::assertSame(1200, $totals['annuel']['exclTax']);
    }

    public function testPaymentScheduleLastInstallmentTakesTheRounding(): void
    {
        $quote = (new Quote())
            ->addLine((new QuoteLine())->setDescription('Site')->setUnitPrice(100000))
            ->addInstallment((new QuoteInstallment())->setLabel('1/3')->setPercent('33.33'))
            ->addInstallment((new QuoteInstallment())->setLabel('2/3')->setPercent('33.33'))
            ->addInstallment((new QuoteInstallment())->setLabel('3/3')->setPercent('33.34'));

        self::assertSame([33330, 33330, 33340], array_column($quote->getPaymentSchedule(), 'amount'));
        self::assertSame(100000, array_sum(array_column($quote->getPaymentSchedule(), 'amount')));
    }

    public function testSubscriptionRenewalAndDueDays(): void
    {
        $subscription = (new Subscription())
            ->setRecurrence(Recurrence::Monthly)
            ->setNextRenewalDate(new \DateTimeImmutable('2026-01-31'));

        self::assertSame(-3, $subscription->getDaysUntilRenewal(new \DateTimeImmutable('2026-02-03')));
        $subscription->setRecurrence(Recurrence::Yearly)->setAmount(1200);
        $subscription->renew();
        self::assertSame('2027-01-31', $subscription->getNextRenewalDate()->format('Y-m-d'));
        self::assertSame(1200, $subscription->getYearlyAmount());
    }
}
