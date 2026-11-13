<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

// Autor: Leonardo (leonardoaderito-cmyk) - cria ou promove uma conta de administrador e define a senha
Artisan::command('cda:admin {email} {--nome=}', function () {
    $email = Str::lower(trim($this->argument('email')));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Email inválido.');

        return 1;
    }

    $utilizador = User::where('email', $email)->first();
    $nova = ! $utilizador;
    $utilizador ??= new User;
    $utilizador->forceFill([
        'name' => $this->option('nome') ?: ($utilizador->name ?: 'Administrador'),
        'email' => $email,
        'is_admin' => true,
        'ativo' => true,
        // Sem nivel de administrador geral a conta ficaria sem acesso a nenhuma seccao
        'nivel_acesso' => 'super_admin',
    ]);
    if ($nova) {
        // Senha aleatoria que ninguem conhece, ate ser definida abaixo
        $utilizador->password = Str::random(40);
    }

    if ($this->input->isInteractive()) {
        $senha = $this->secret('Nova senha (mínimo 8 caracteres; Enter para não alterar)');
        if ($senha !== null && $senha !== '') {
            if (strlen($senha) < 8 || $senha !== $this->secret('Repita a senha')) {
                $this->error('A senha tem menos de 8 caracteres ou as duas não coincidem. Nada foi alterado.');

                return 1;
            }
            $utilizador->password = $senha;
        }
    }

    $utilizador->save();
    $this->info(($nova ? 'Conta de administrador criada: ' : 'Conta de administrador actualizada: ').$email);
    if ($nova && ! $this->input->isInteractive()) {
        $this->line('Ainda sem senha utilizável. Corra o comando de novo, sem --no-interaction, para a definir.');
    }

    return 0;
})->purpose('Cria ou promove uma conta de administrador e define a senha');

// Leonardo (leonardoaderito-cmyk): conta de administrador inicial do pacote para alojamento partilhado.
// Entra com o utilizador e a senha indicados e e obrigada a definir email e senha pessoais no primeiro acesso.
Artisan::command('cda:admin-inicial {utilizador=admin} {senha=admin}', function () {
    $utilizador = Str::lower(trim($this->argument('utilizador')));
    if (User::where('email', $utilizador)->exists()) {
        $this->info("A conta {$utilizador} ja existe: nada foi alterado.");

        return 0;
    }

    $conta = new User;
    $conta->forceFill([
        'name' => 'Administrador',
        'email' => $utilizador,
        'password' => $this->argument('senha'),
        'is_admin' => true,
        'ativo' => true,
        'nivel_acesso' => 'super_admin',
        'trocar_senha' => true,
    ])->save();
    $this->info("Conta inicial criada: {$utilizador} (senha provisoria, a trocar no primeiro acesso).");

    return 0;
})->purpose('Cria a conta de administrador inicial com senha provisoria');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Leonardo (leonardoaderito-cmyk): copia de seguranca diaria (precisa do cron "php artisan schedule:run" a cada minuto)
Schedule::command('cda:copia-seguranca')->dailyAt('02:00');
