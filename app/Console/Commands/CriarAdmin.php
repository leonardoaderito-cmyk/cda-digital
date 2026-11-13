<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CriarAdmin extends Command
{
    protected $signature = 'cda:criar-admin {email} {--nome=Administrador CDA}';

    protected $description = 'Cria ou promove um administrador do portal';

    public function handle(): int
    {
        $senha = $this->secret('Senha (minimo 8 caracteres)');

        if (strlen((string) $senha) < 8) {
            $this->error('Senha demasiado curta.');

            return self::FAILURE;
        }

        $admin = User::firstOrNew(['email' => strtolower($this->argument('email'))]);
        $admin->name = $admin->name ?: $this->option('nome');
        $admin->password = $senha;
        $admin->is_admin = true;
        $admin->ativo = true;
        $admin->aprovado_em = now();
        $admin->save();

        $this->info('Administrador pronto: '.$admin->email);

        return self::SUCCESS;
    }
}
