<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

// Leonardo (leonardoaderito-cmyk): copia de seguranca da base de dados e dos ficheiros carregados num unico zip
class CopiaSeguranca extends Command
{
    protected $signature = 'cda:copia-seguranca {--apenas-base : Copia só a base de dados, sem os ficheiros carregados}';

    protected $description = 'Cria uma cópia de segurança (base de dados, documentos, imagens e talões) e apaga as mais antigas';

    public function handle(): int
    {
        $ligacao = DB::connection();
        if ($ligacao->getDriverName() !== 'sqlite') {
            $this->error('Este comando só copia bases SQLite. Use a ferramenta de cópias do servidor de base de dados.');

            return self::FAILURE;
        }

        $pasta = rtrim(config('cda.copias.pasta'), '/\\');
        File::ensureDirectoryExists($pasta);
        $nome = 'cda-digital-'.now()->format('Y-m-d-His').($this->option('apenas-base') ? '-base' : '').'.zip';
        $destino = $pasta.DIRECTORY_SEPARATOR.$nome;
        $temporaria = $pasta.DIRECTORY_SEPARATOR.'.base-'.Str::random(10).'.sqlite';

        try {
            // VACUUM INTO gera uma copia coerente mesmo com o portal a escrever na base
            $ligacao->statement('VACUUM INTO ?', [$temporaria]);

            $zip = new ZipArchive;
            if ($zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Não foi possível criar '.$destino);
            }
            $zip->addFile($temporaria, 'database/database.sqlite');
            $ficheiros = 1;

            if (! $this->option('apenas-base')) {
                $raiz = $this->normalizar(base_path()).'/';
                foreach (config('cda.copias.pastas') as $rotulo => $caminho) {
                    if (! is_dir($caminho)) {
                        continue;
                    }
                    $base = $this->normalizar($caminho);
                    // Guardado com o caminho relativo ao projecto, para repor basta extrair na raiz
                    $prefixo = str_starts_with($base.'/', $raiz) ? Str::after($base, $raiz) : $rotulo;
                    foreach (File::allFiles($caminho, true) as $ficheiro) {
                        $zip->addFile($ficheiro->getPathname(), $prefixo.'/'.$this->normalizar($ficheiro->getRelativePathname()));
                        $ficheiros++;
                    }
                }
            }

            $zip->addFromString('LEIA-ME.txt', implode("\n", [
                'Cópia de segurança CDA Digital — '.now()->format('d/m/Y H:i'),
                '',
                'Para repor: colocar o portal em manutenção (php artisan down),',
                'extrair este ficheiro na pasta do projecto (substitui database/database.sqlite',
                'e os ficheiros carregados) e voltar a abrir (php artisan up).',
                '',
            ]));
            $zip->close();
        } catch (Throwable $erro) {
            File::delete([$temporaria, $destino]);
            Log::error('Cópia de segurança falhou', ['erro' => $erro->getMessage()]);
            $this->error('A cópia de segurança falhou: '.$erro->getMessage());

            return self::FAILURE;
        } finally {
            File::delete($temporaria);
        }

        $apagadas = $this->apagarAntigas($pasta, max(1, (int) config('cda.copias.manter')));
        $tamanho = number_format(filesize($destino) / 1048576, 1, ',', '.');
        Log::info('Cópia de segurança criada', ['ficheiro' => $destino, 'ficheiros' => $ficheiros, 'apagadas' => $apagadas]);
        $this->info("Cópia criada: {$destino} ({$ficheiros} ficheiros, {$tamanho} MB).");
        if ($apagadas) {
            $this->line("Apagadas {$apagadas} cópias antigas.");
        }

        return self::SUCCESS;
    }

    private function apagarAntigas(string $pasta, int $manter): int
    {
        // O nome comeca pela data, por isso a ordem alfabetica e a cronologica
        $copias = collect(File::glob($pasta.DIRECTORY_SEPARATOR.'cda-digital-*.zip'))->sort()->values();
        $antigas = $copias->slice(0, max(0, $copias->count() - $manter));
        File::delete($antigas->all());

        return $antigas->count();
    }

    private function normalizar(string $caminho): string
    {
        return rtrim(str_replace('\\', '/', $caminho), '/');
    }
}
