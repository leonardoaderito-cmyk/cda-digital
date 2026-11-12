<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\EventoController;
use App\Http\Controllers\Admin\ImagemController;
use App\Http\Controllers\Admin\PaginaController as AdminPaginaController;
use App\Http\Controllers\Admin\SlideController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistoController;
use App\Http\Controllers\MembroController;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\PublicoController;
use Illuminate\Support\Facades\Route;

// Paginas de conteudo com endereco proprio; o texto e gerido em Administracao > Paginas
foreach (['instituicao', 'historia', 'orgaos', 'estatutos', 'parceiros', 'cookies'] as $pagina) {
    Route::get('/'.$pagina, [PaginaController::class, 'mostrar'])->name($pagina);
}
Route::get('/pagina/{slug}', [PaginaController::class, 'mostrar'])->name('pagina');

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
});

Route::middleware('auth')->group(function () {
    Route::post('/sair', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/membro', [MembroController::class, 'area'])->name('membro.area');
    Route::post('/membro/requerimentos', [MembroController::class, 'requerimento'])->name('membro.requerimento');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->controller(AdminController::class)->group(function () {
    Route::get('/', 'painel')->name('painel');
    Route::get('/membros', 'membros')->name('membros');
    Route::post('/membros/{user}/aprovar', 'aprovar')->name('membros.aprovar');
    Route::post('/membros/{user}/desactivar', 'desactivar')->name('membros.desactivar');
    Route::get('/quotas', 'quotas')->name('quotas');
    Route::post('/quotas/{quota}', 'actualizarQuota')->name('quotas.actualizar');
    Route::get('/requerimentos', 'requerimentos')->name('requerimentos');
    Route::post('/requerimentos/{requerimento}', 'responderRequerimento')->name('requerimentos.responder');
    Route::get('/documentos', 'documentos')->name('documentos');
    Route::post('/documentos', 'guardarDocumento')->name('documentos.guardar');
    Route::delete('/documentos/{documento}', 'apagarDocumento')->name('documentos.apagar');
    Route::get('/noticias', 'noticias')->name('noticias');
    Route::get('/noticias/nova', 'novaNoticia')->name('noticias.nova');
    Route::post('/noticias', 'guardarNoticia')->name('noticias.guardar');
    Route::get('/noticias/{noticia}/editar', 'editarNoticia')->name('noticias.editar');
    Route::post('/noticias/{noticia}', 'actualizarNoticia')->name('noticias.actualizar');
    Route::post('/noticias/{noticia}/alternar', 'alternarNoticia')->name('noticias.alternar');
    Route::delete('/noticias/{noticia}', 'apagarNoticia')->name('noticias.apagar');
    Route::get('/mensagens', 'mensagens')->name('mensagens');
    Route::post('/mensagens/{mensagem}/ler', 'lerMensagem')->name('mensagens.ler');
    Route::get('/newsletter', 'newsletter')->name('newsletter');
    Route::get('/newsletter/exportar', 'exportarNewsletter')->name('newsletter.exportar');
    Route::post('/newsletter/{newsletterSubscricao}/alternar', 'alternarNewsletter')->name('newsletter.alternar');
});

Route::prefix('admin/eventos')->name('admin.eventos')->middleware(['auth', 'admin'])->controller(EventoController::class)->group(function () {
    Route::get('/', 'index')->name('');
    Route::get('/novo', 'create')->name('.novo');
    Route::post('/', 'store')->name('.guardar');
    Route::get('/{evento}/editar', 'edit')->name('.editar');
    Route::post('/{evento}', 'update')->name('.actualizar');
    Route::post('/{evento}/alternar', 'alternar')->name('.alternar');
    Route::delete('/{evento}', 'destroy')->name('.apagar');
});

Route::prefix('admin/paginas')->name('admin.paginas')->middleware(['auth', 'admin'])->controller(AdminPaginaController::class)->group(function () {
    Route::get('/', 'index')->name('');
    Route::get('/nova', 'create')->name('.nova');
    Route::post('/', 'store')->name('.guardar');
    Route::get('/{pagina}/editar', 'edit')->name('.editar');
    Route::post('/{pagina}', 'update')->name('.actualizar');
    Route::post('/{pagina}/alternar', 'alternar')->name('.alternar');
    Route::delete('/{pagina}', 'destroy')->name('.apagar');
});

Route::prefix('admin/carrossel')->name('admin.slides')->middleware(['auth', 'admin'])->controller(SlideController::class)->group(function () {
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

Route::prefix('admin/imagens')->name('admin.imagens')->middleware(['auth', 'admin'])->controller(ImagemController::class)->group(function () {
    Route::get('/', 'index')->name('');
    Route::post('/', 'store')->name('.guardar');
    Route::post('/{imagem}', 'update')->name('.actualizar');
    Route::delete('/{imagem}', 'destroy')->name('.apagar');
});
