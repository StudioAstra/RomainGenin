<?php

namespace App\Service;

use App\Entity\Quote;
use App\Repository\CompanyRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Environment;

class QuotePdfRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly CompanyRepository $companies,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function render(Quote $quote): string
    {
        $html = $this->twig->render('quote/pdf.html.twig', [
            'quote' => $quote,
            'company' => $this->companies->findCurrent(),
        ]);

        $options = (new Options())
            ->setDefaultFont('DejaVu Sans')
            ->setIsRemoteEnabled(false)
            ->setIsPhpEnabled(false)
            ->setChroot(\dirname(__DIR__, 2).'/templates');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        // Numéro de page en bas à droite (counter(pages) n'est pas géré en CSS par Dompdf)
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 62, $canvas->get_height() - 40, '{PAGE_NUM} / {PAGE_COUNT}', $font, 7, [0.35, 0.37, 0.36]);

        return (string) $dompdf->output();
    }

    public function filename(Quote $quote): string
    {
        return \sprintf('Devis %s - %s.pdf', $quote->getNumber() ?? 'brouillon', $this->slugger->slug((string) $quote->getClient()?->getName())->toString());
    }
}
