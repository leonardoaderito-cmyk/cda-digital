<?php

namespace App\Support;

// Autor: Leonardo (leonardoaderito-cmyk) - selo oficial da CDA (SVG) para declaracoes, comprovativos e extractos.
// Usa so cores solidas, sem gradientes nem textPath, para sair igual no browser e no PDF (dompdf).
class Selo
{
    /** Selo dourado com anel vermelho: $centro e a palavra principal (ex.: QUITAÇÃO, PAGO), $rodape vai por baixo (ex.: o ano). */
    public static function svg(string $centro, string $rodape = ''): string
    {
        $c = 100;
        $partes = [];

        // Bordo serrilhado dourado (estrela de 60 pontas)
        $pontos = [];
        for ($i = 0; $i < 120; $i++) {
            $raio = $i % 2 ? 91 : 99;
            $angulo = deg2rad($i * 3);
            $pontos[] = round($c + $raio * sin($angulo), 2).','.round($c - $raio * cos($angulo), 2);
        }
        $partes[] = '<polygon points="'.implode(' ', $pontos).'" fill="#e0a800" stroke="#9c7000" stroke-width="1"/>';
        $partes[] = '<circle cx="100" cy="100" r="87" fill="#ffd23f" stroke="#9c7000" stroke-width="1.5"/>';
        // Anel vermelho com o nome da Camara
        $partes[] = '<circle cx="100" cy="100" r="82" fill="#b5121b"/>';
        $partes[] = '<circle cx="100" cy="100" r="79" fill="none" stroke="#ffd23f" stroke-width=".8"/>';
        $partes[] = '<circle cx="100" cy="100" r="58" fill="none" stroke="#ffd23f" stroke-width=".8"/>';
        $partes[] = self::textoCircular('CÂMARA DOS DESPACHANTES ADUANEIROS', 68, -112, 112);
        $partes[] = self::textoCircular('MOÇAMBIQUE', 68, 157, 203, true);
        $partes[] = self::estrela(100 - 68 * sin(deg2rad(45)), 100 + 68 * cos(deg2rad(45)), 3.6);
        $partes[] = self::estrela(100 + 68 * sin(deg2rad(45)), 100 + 68 * cos(deg2rad(45)), 3.6);
        // Centro dourado brilhante
        $partes[] = '<circle cx="100" cy="100" r="55" fill="#ffd23f" stroke="#9c7000" stroke-width="1.2"/>';
        $partes[] = '<circle cx="100" cy="100" r="50" fill="none" stroke="#b5121b" stroke-width=".8" stroke-dasharray="2 2"/>';
        $partes[] = '<ellipse cx="80" cy="72" rx="20" ry="9" fill="#ffffff" fill-opacity=".45" transform="rotate(-30 80 72)"/>';
        $partes[] = '<text x="100" y="100" text-anchor="middle" font-family="DejaVu Sans, Arial, sans-serif" font-size="30" font-weight="bold" fill="#b5121b">CDA</text>';
        $partes[] = '<rect x="58" y="107" width="84" height="15" fill="#b5121b"/>';
        $tamanho = mb_strlen($centro) > 8 ? 9.5 : 11;
        $partes[] = '<text x="100" y="118.2" text-anchor="middle" font-family="DejaVu Sans, Arial, sans-serif" font-size="'.$tamanho.'" font-weight="bold" fill="#ffffff">'.e(mb_strtoupper($centro)).'</text>';
        if ($rodape !== '') {
            $partes[] = '<text x="100" y="137" text-anchor="middle" font-family="DejaVu Sans, Arial, sans-serif" font-size="11" font-weight="bold" fill="#5c4200">'.e($rodape).'</text>';
        }
        $partes[] = self::estrela(100, 66, 4.2, '#b5121b');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">'.implode('', $partes).'</svg>';
    }

    public static function dataUri(string $centro, string $rodape = ''): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($centro, $rodape));
    }

    /** Escreve letra a letra sobre um arco (graus a partir do topo, no sentido dos ponteiros). Em baixo, $inferior vira as letras. */
    private static function textoCircular(string $texto, float $raio, float $inicio, float $fim, bool $inferior = false): string
    {
        $letras = mb_str_split($texto);
        $total = count($letras);
        $passo = $total > 1 ? ($fim - $inicio) / ($total - 1) : 0;
        $saida = '';
        foreach ($letras as $i => $letra) {
            if ($letra === ' ') {
                continue;
            }
            $graus = $inferior ? $fim - $i * $passo : $inicio + $i * $passo;
            $angulo = deg2rad($graus);
            // A linha de base fica ligeiramente para dentro (em baixo) ou para fora (em cima) para centrar as letras no anel
            $r = $inferior ? $raio + 4 : $raio - 4;
            $x = round(100 + $r * sin($angulo), 2);
            $y = round(100 - $r * cos($angulo), 2);
            $rotacao = round($inferior ? $graus + 180 : $graus, 2);
            $saida .= '<text x="'.$x.'" y="'.$y.'" text-anchor="middle" transform="rotate('.$rotacao.' '.$x.' '.$y.')" font-family="DejaVu Sans, Arial, sans-serif" font-size="9.6" font-weight="bold" fill="#ffffff">'.e($letra).'</text>';
        }

        return $saida;
    }

    private static function estrela(float $x, float $y, float $raio, string $cor = '#ffd23f'): string
    {
        $pontos = [];
        for ($i = 0; $i < 10; $i++) {
            $r = $i % 2 ? $raio * .45 : $raio;
            $a = deg2rad($i * 36);
            $pontos[] = round($x + $r * sin($a), 2).','.round($y - $r * cos($a), 2);
        }

        return '<polygon points="'.implode(' ', $pontos).'" fill="'.$cor.'"/>';
    }
}
