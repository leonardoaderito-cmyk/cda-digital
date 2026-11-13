<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

class PdfService
{
    public function descarregar(string $html, string $nome): Response
    {
        $opcoes = new Options;
        $opcoes->set('defaultFont', 'DejaVu Sans');
        $opcoes->set('isRemoteEnabled', false);
        $opcoes->setChroot(public_path());

        $pdf = new Dompdf($opcoes);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nome.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
