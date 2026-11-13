<?php

namespace App\Providers;

use App\Models\Documento;
use App\Models\Evento;
use App\Models\Noticia;
use App\Models\Pagina;
use App\Services\Assistente\Indice;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // O estado das paginas e lido de novo em cada arranque da aplicacao
        Pagina::esquecerEstados();

        // Leonardo (leonardoaderito-cmyk): atras de Cloudflare ou proxy, aceitar X-Forwarded-* para detectar HTTPS e o IP real
        $proxies = trim((string) config('cda.proxies'));
        if ($proxies !== '') {
            TrustProxies::at($proxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $proxies)))));
        }

        // Leonardo (leonardoaderito-cmyk): limite de perguntas ao Assistente CDA por visitante
        RateLimiter::for('assistente', fn (Request $request) => Limit::perMinute(max(1, (int) config('cda.assistente.por_minuto')))
            ->by($request->ip())
            ->response(fn () => response()->json([
                'resposta' => 'Fez muitas perguntas seguidas. Aguarde um minuto e tente de novo.',
                'fontes' => [],
                'modo' => 'limite',
            ], 429)));

        // Leonardo (leonardoaderito-cmyk): o Assistente CDA acompanha o que e publicado, editado ou apagado
        foreach ([Documento::class, Pagina::class, Noticia::class, Evento::class] as $modelo) {
            $modelo::saved(fn ($m) => app(Indice::class)->actualizar($m));
            $modelo::deleted(fn ($m) => app(Indice::class)->actualizar($m, true));
        }
    }
}
