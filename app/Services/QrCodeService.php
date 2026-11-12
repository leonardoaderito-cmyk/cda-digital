<?php

namespace App\Services;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    public function dataUri(string $conteudo): string
    {
        $resultado = (new PngWriter)->write(new QrCode(
            data: $conteudo,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 280,
            margin: 12,
        ));

        return 'data:'.$resultado->getMimeType().';base64,'.base64_encode($resultado->getString());
    }
}
