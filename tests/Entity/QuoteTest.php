<?php

namespace App\Tests\Entity;

use App\Entity\Product;
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
}
