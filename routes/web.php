<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\EventoController;
use App\Http\Controllers\Admin\ImagemController;
use App\Http\Controllers\Admin\PaginaController as AdminPaginaController;
use App\Http\Controllers\Admin\SlideController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistoController;
use App\Http\Controllers\Auth\SenhaController;
use App\Http\Controllers\MembroController;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\PublicoController;
use Illuminate\Support\Facades\Route;

// Paginas de conteudo com endereco proprio; o texto e gerido em Administracao > Paginas
foreach (['instituicao', 'historia', 'orgaos', 'estatutos', 'parceiros', 'cookies'] as $pagina) {
    Route::get('/'.$pagina, [PaginaController::class, 'mostrar'])->name($pagina);
}
Route::get('/pagina/{slug}', [PaginaController::class, 'mostrar'])->name('pagina');
Route::get('/verificar-regularidade/{user}', [MembroController::class, 'verificarRegularidade'])
    ->middleware('signed')->name('regularidade.verificar');

Route::controller(PublicoController::class)->group(function () {
    Route::get('/', 'inicio')->name('inicio');
    Route::post('/newsletter', 'subscreverNewsletter')->middleware('throttle:5,10')->name('newsletter.subscrever');

    // Cada seccao pode ser desactivada em Administracao > Paginas
    Route::middleware('pagina:noticias')->group(function () {
        Route::get('/noticias', 'noticias')->name('noticias');
        Route::get('/noticias/{slug}', 'noticia')->name('noticia');
    });
    Route::middleware('pagina:eventos')->group(function () {
        Route::get('/eventos', 'eventos')->name('eventos');
        Route::get('/eventos/{slug}', 'evento')->name('evento');
    });
    Route::get('/actividades', 'actividades')->middleware('pagina:actividades')->name('actividades');
    Route::middleware('pagina:documentos')->group(function () {
        Route::get('/documentos', 'documentos')->name('documentos');
        Route::get('/documentos/{documento}/abrir', 'documento')->name('documento.abrir');
    });
    Route::get('/membros', 'directorio')->middleware('pagina:directorio')->name('directorio');
    Route::middleware('pagina:contactos')->group(function () {
        Route::get('/contactos', 'contactos')->name('contactos');
        Route::post('/contactos', 'enviarContacto')->middleware('throttle:5,10')->name('contactos.enviar');
    });
});

Route::middleware('guest')->group(function () {
    Route::get('/entrar', [LoginController::class, 'create'])->name('login');
    Route::post('/entrar', [LoginController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/registar', [RegistoController::class, 'create'])->name('registo');
    Route::post('/registar', [RegistoController::class, 'store'])->middleware('throttle:5,10');
    Route::get('/recuperar-senha', [SenhaController::class, 'pedir'])->name('password.request');
    Route::post('/recuperar-senha', [SenhaController::class, 'enviar'])->middleware('throttle:3,10')->name('password.email');
    Route::get('/definir-senha/{token}', [SenhaController::class, 'editar'])->name('password.reset');
    Route::post('/definir-senha', [SenhaController::class, 'actualizar'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/sair', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/membro', [MembroController::class, 'area'])->name('membro.area');
    Route::post('/membro/requerimentos', [MembroController::class, 'requerimento'])->name('membro.requerimento');
    Route::post('/membro/senha', [MembroController::class, 'alterarSenha'])->name('membro.senha');
    Route::get('/membro/quotas/{quota}/comprovativo', [MembroController::class, 'comprovativo'])->name('membro.comprovativo');
    Route::get('/membro/quotas/{quota}/comprovativo/descarregar', [MembroController::class, 'descarregarComprovativo'])->name('membro.comprovativo.descarregar');
    Route::get('/membro/declaracao-quitacao', [MembroController::class, 'declaracao'])->name('membro.declaracao');
    Route::get('/membro/declaracao-quitacao/descarregar', [MembroController::class, 'descarregarDeclaracao'])->name('membro.declaracao.descarregar');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->controller(AdminController::class)->group(function () {
    Route::get('/', 'painel')->name('painel');
    Route::get('/utilizadores', 'utilizadores')->middleware('permissao:utilizadores.gerir')->name('utilizadores');
    Route::post('/utilizadores', 'criarUtilizador')->middleware('permissao:utilizadores.gerir')->name('utilizadores.criar');
    Route::post('/utilizadores/{user}', 'actualizarUtilizador')->middleware('permissao:utilizadores.gerir')->name('utilizadores.actualizar');
    Route::post('/utilizadores/{user}/credenciais', 'enviarAcessoAdmin')->middleware('permissao:utilizadores.gerir')->name('utilizadores.credenciais');
    Route::get('/membros', 'membros')->middleware('permissao:membros.ver')->name('membros');
    Route::post('/membros', 'criarMembro')->middleware('permissao:membros.gerir')->name('membros.criar');
    Route::post('/membros/{user}/aprovar', 'aprovar')->middleware('permissao:membros.gerir')->name('membros.aprovar');
    Route::post('/membros/{user}/desactivar', 'desactivar')->middleware('permissao:membros.gerir')->name('membros.desactivar');
    Route::post('/membros/{user}/credenciais', 'enviarCredenciais')->middleware('permissao:membros.gerir')->name('membros.credenciais');
    Route::get('/quotas', 'quotas')->middleware('permissao:financeiro.ver')->name('quotas');
    Route::post('/quotas', 'criarQuota')->middleware('permissao:financeiro.gerir')->name('quotas.criar');
    Route::post('/quotas/lancar', 'lancarQuotas')->middleware('permissao:financeiro.gerir')->name('quotas.lancar');
    Route::post('/quotas/{quota}', 'actualizarQuota')->middleware('permissao:financeiro.gerir')->name('quotas.actualizar');
    Route::get('/requerimentos', 'requerimentos')->middleware('permissao:membros.ver')->name('requerimentos');
    Route::post('/requerimentos/{requerimento}', 'responderRequerimento')->middleware('permissao:requerimentos.gerir')->name('requerimentos.responder');
    Route::get('/documentos', 'documentos')->middleware('permissao:conteudo.gerir')->name('documentos');
    Route::post('/documentos', 'guardarDocumento')->middleware('permissao:conteudo.gerir')->name('documentos.guardar');
    Route::delete('/documentos/{documento}', 'apagarDocumento')->middleware('permissao:conteudo.gerir')->name('documentos.apagar');
    Route::get('/noticias', 'noticias')->middleware('permissao:conteudo.gerir')->name('noticias');
    Route::get('/noticias/nova', 'novaNoticia')->middleware('permissao:conteudo.gerir')->name('noticias.nova');
    Route::post('/noticias', 'guardarNoticia')->middleware('permissao:conteudo.gerir')->name('noticias.guardar');
    Route::get('/noticias/{noticia}/editar', 'editarNoticia')->middleware('permissao:conteudo.gerir')->name('noticias.editar');
    Route::post('/noticias/{noticia}', 'actualizarNoticia')->middleware('permissao:conteudo.gerir')->name('noticias.actualizar');
    Route::post('/noticias/{noticia}/alternar', 'alternarNoticia')->middleware('permissao:conteudo.gerir')->name('noticias.alternar');
    Route::delete('/noticias/{noticia}', 'apagarNoticia')->middleware('permissao:conteudo.gerir')->name('noticias.apagar');
    Route::get('/mensagens', 'mensagens')->middleware('permissao:comunicacao.gerir')->name('mensagens');
    Route::post('/mensagens/{mensagem}/ler', 'lerMensagem')->middleware('permissao:comunicacao.gerir')->name('mensagens.ler');
    Route::get('/newsletter', 'newsletter')->middleware('permissao:comunicacao.gerir')->name('newsletter');
    Route::get('/newsletter/exportar', 'exportarNewsletter')->middleware('permissao:comunicacao.gerir')->name('newsletter.exportar');
    Route::post('/newsletter/{newsletterSubscricao}/alternar', 'alternarNewsletter')->middleware('permissao:comunicacao.gerir')->name('newsletter.alternar');
});

Route::prefix('admin/eventos')->name('admin.eventos')->middleware(['auth', 'admin', 'permissao:conteudo.gerir'])->controller(EventoController::class)->group(function () {
    Route::get('/', 'index')->name('');
    Route::get('/novo', 'create')->name('.novo');
    Route::post('/', 'store')->name('.guardar');
    Route::get('/{evento}/editar', 'edit')->name('.editar');
    Route::post('/{evento}', 'update')->name('.actualizar');
    Route::post('/{evento}/alternar', 'alternar')->name('.alternar');
    Route::delete('/{evento}', 'destroy')->name('.apagar');
});

Route::prefix('admin/paginas')->name('admin.paginas')->middleware(['auth', 'admin', 'permissao:conteudo.gerir'])->controller(AdminPaginaController::class)->group(function () {
    Route::get('/', 'index')->name('');
    Route::get('/nova', 'create')->name('.nova');
    Route::post('/', 'store')->name('.guardar');
    Route::get('/{pagina}/editar', 'edit')->name('.editar');
    Route::post('/{pagina}', 'update')->name('.actualizar');
    Route::post('/{pagina}/alternar', 'alternar')->name('.alternar');
    Route::delete('/{pagina}', 'destroy')->name('.apagar');
});

Route::prefix('admin/carrossel')->name('admin.slides')->middleware(['auth', 'admin', 'permissao:conteudo.gerir'])->controller(SlideController::class)->group(function () {
    Route::get('/', 'index')->name('');
    Route::get('/novo', 'create')->name('.novo');
    Route::post('/', 'store')->name('.guardar');
    Route::post('/da-noticia', 'daNoticia')->name('.da-noticia');
    Route::get('/{slide}/editar', 'edit')->name('.editar');
    Route::post('/{slide}', 'update')->name('.actualizar');
    Route::post('/{slide}/alternar', 'alternar')->name('.alternar');
    Route::post('/{slide}/mover', 'mover')->name('.mover');
    Route::delete('/{slide}', 'destroy')->name('.apagar');
});

Route::prefix('admin/imagens')->name('admin.imagens')->middleware(['auth', 'admin', 'permissao:conteudo.gerir'])->controller(ImagemController::class)->group(function () {
    Route::get('/', 'index')->name('');
    Route::post('/', 'store')->name('.guardar');
    Route::post('/{imagem}', 'update')->name('.actualizar');
    Route::delete('/{imagem}', 'destroy')->name('.apagar');
});
