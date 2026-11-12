<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\EventoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistoController;
use App\Http\Controllers\MembroController;
use App\Http\Controllers\PublicoController;
use Illuminate\Support\Facades\Route;

foreach (['instituicao', 'historia', 'orgaos', 'estatutos', 'parceiros'] as $pagina) {
    Route::view('/'.$pagina, 'institucional.'.$pagina)->name($pagina);
}

Route::controller(PublicoController::class)->group(function () {
    Route::get('/', 'inicio')->name('inicio');
    Route::get('/noticias', 'noticias')->name('noticias');
    Route::get('/noticias/{slug}', 'noticia')->name('noticia');
    Route::get('/eventos', 'eventos')->name('eventos');
    Route::get('/eventos/{slug}', 'evento')->name('evento');
    Route::get('/actividades', 'actividades')->name('actividades');
    Route::get('/documentos', 'documentos')->name('documentos');
    Route::get('/documentos/{documento}/abrir', 'documento')->name('documento.abrir');
    Route::get('/membros', 'directorio')->name('directorio');
    Route::get('/contactos', 'contactos')->name('contactos');
    Route::post('/contactos', 'enviarContacto')->middleware('throttle:5,10')->name('contactos.enviar');
    Route::post('/newsletter', 'subscreverNewsletter')->middleware('throttle:5,10')->name('newsletter.subscrever');
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
    Route::post('/noticias', 'guardarNoticia')->name('noticias.guardar');
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
