<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Autor: Leonardo (leonardoaderito-cmyk) - paginas editaveis na administracao e biblioteca de imagens
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paginas', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('titulo');
            $table->string('resumo', 500)->nullable();
            $table->longText('conteudo')->nullable();
            // "conteudo": texto editavel; "sistema": seccao do portal que so pode ser ligada/desligada
            $table->string('tipo', 20)->default('conteudo');
            $table->boolean('activa')->default(true);
            $table->boolean('no_menu')->default(false);
            $table->timestamps();
        });

        Schema::create('imagens', function (Blueprint $table) {
            $table->id();
            $table->string('ficheiro');
            $table->string('nome');
            $table->string('alt')->nullable();
            $table->unsignedInteger('tamanho')->default(0);
            $table->unsignedInteger('largura')->nullable();
            $table->unsignedInteger('altura')->nullable();
            $table->timestamps();
        });

        $agora = now();

        // Seccoes do portal: podem ser activadas ou desactivadas, mas o conteudo vem da base de dados
        foreach ([
            'noticias' => 'Notícias',
            'eventos' => 'Eventos',
            'actividades' => 'Actividades',
            'documentos' => 'Centro documental',
            'directorio' => 'Directório de membros',
            'contactos' => 'Contactos',
        ] as $slug => $titulo) {
            DB::table('paginas')->insert(['slug' => $slug, 'titulo' => $titulo, 'tipo' => 'sistema', 'activa' => true, 'created_at' => $agora, 'updated_at' => $agora]);
        }

        // Paginas institucionais: o texto inicial vem das vistas originais
        foreach (['instituicao', 'historia', 'orgaos', 'estatutos', 'parceiros'] as $slug) {
            $ficheiro = resource_path('views/institucional/'.$slug.'.blade.php');
            $fonte = is_file($ficheiro) ? file_get_contents($ficheiro) : '';
            preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $fonte, $titulo);
            preg_match('/<\/h1>\s*<p[^>]*>(.*?)<\/p>/s', $fonte, $resumo);
            preg_match('/<div class="conteudo-texto">(.*)<\/div>\s*@endsection/s', $fonte, $conteudo);

            DB::table('paginas')->insert([
                'slug' => $slug,
                'titulo' => html_entity_decode(trim($titulo[1] ?? ucfirst($slug))),
                'resumo' => isset($resumo[1]) ? html_entity_decode(trim($resumo[1])) : null,
                'conteudo' => trim($conteudo[1] ?? ''),
                'tipo' => 'conteudo',
                'activa' => true,
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }

        DB::table('paginas')->insert([
            'slug' => 'cookies',
            'titulo' => 'Política de cookies',
            'resumo' => 'Como o portal da CDA utiliza cookies.',
            'conteudo' => <<<'HTML'
<h2>O que são cookies</h2>
<p>Cookies são pequenos ficheiros de texto que um sítio web guarda no seu navegador para se lembrar de informação entre páginas e entre visitas.</p>
<h2>Cookies que este portal utiliza</h2>
<ul>
<li><strong>Cookie de sessão</strong> — mantém a sua sessão iniciada na área de membro e na administração. É apagado quando termina a sessão ou expira.</li>
<li><strong>Cookie de segurança (XSRF-TOKEN)</strong> — protege os formulários do portal contra pedidos forjados.</li>
<li><strong>Cookie de preferência (cda_cookies)</strong> — guarda a escolha que fez no aviso de cookies, durante 180 dias.</li>
</ul>
<p>Estes cookies são essenciais ao funcionamento do portal. O portal não utiliza cookies de publicidade nem de seguimento de terceiros.</p>
<h2>Como gerir os cookies</h2>
<p>Pode apagar ou bloquear os cookies nas definições do seu navegador. Se bloquear os cookies essenciais, não conseguirá iniciar sessão nem enviar formulários.</p>
<h2>Contacto</h2>
<p>Para qualquer questão sobre esta política, utilize a página de <a href="/contactos">Contactos</a>.</p>
HTML,
            'tipo' => 'conteudo',
            'activa' => true,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('imagens');
        Schema::dropIfExists('paginas');
    }
};
