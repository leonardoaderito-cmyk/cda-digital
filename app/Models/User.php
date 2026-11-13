<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'carteira', 'telefone', 'entidade', 'senha_definida_em', 'nivel_acesso'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const NIVEIS_ACESSO = [
        'super_admin' => 'Administrador geral',
        'adesoes' => 'Gestor de adesões',
        'financeiro' => 'Gestor financeiro',
        'editor' => 'Editor de conteúdos',
        'standard' => 'Utilizador standard',
    ];

    private const PERMISSOES = [
        'super_admin' => ['*'],
        'adesoes' => ['membros.ver', 'membros.gerir', 'requerimentos.gerir'],
        'financeiro' => ['membros.ver', 'financeiro.ver', 'financeiro.gerir'],
        'editor' => ['conteudo.gerir', 'comunicacao.gerir'],
        'standard' => ['membros.ver', 'financeiro.ver'],
    ];

    public function quotas(): HasMany
    {
        return $this->hasMany(Quota::class);
    }

    public function requerimentos(): HasMany
    {
        return $this->hasMany(Requerimento::class);
    }

    public function pode(string $permissao): bool
    {
        if (! $this->is_admin || ! $this->ativo) {
            return false;
        }

        $permissoes = self::PERMISSOES[$this->nivel_acesso] ?? [];

        return in_array('*', $permissoes, true) || in_array($permissao, $permissoes, true);
    }

    public function nivelLabel(): string
    {
        return self::NIVEIS_ACESSO[$this->nivel_acesso] ?? 'Sem nível';
    }

    public function estaRegular(?int $ano = null): bool
    {
        $ano ??= now()->year;
        $quotas = $this->quotas()->where('ano', '<=', $ano)->get();
        $quotaActual = $quotas->firstWhere('ano', $ano);

        return $quotaActual
            && in_array($quotaActual->estado, ['paga', 'isenta'], true)
            && ! $quotas->contains(fn (Quota $quota) => in_array($quota->estado, ['pendente', 'em_atraso'], true));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'ativo' => 'boolean',
            'aprovado_em' => 'datetime',
            'ultimo_login' => 'datetime',
            'credenciais_enviadas_em' => 'datetime',
            'senha_definida_em' => 'datetime',
            'trocar_senha' => 'boolean',
        ];
    }
}
