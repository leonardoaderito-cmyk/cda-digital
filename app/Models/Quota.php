<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'ano', 'valor', 'estado', 'referencia', 'data_pagamento', 'talao', 'talao_estado', 'talao_referencia', 'talao_data', 'talao_nota', 'talao_enviado_em'])]
class Quota extends Model
{
    protected function casts(): array
    {
        return ['data_pagamento' => 'date', 'talao_data' => 'date', 'talao_enviado_em' => 'datetime'];
    }

    /** Valor da quota de um ano: o valor mais usado nas quotas ja lancadas para esse ano (0 se ainda nao houver). */
    public static function valorDoAno(int $ano): int
    {
        return (int) static::where('ano', $ano)->where('valor', '>', 0)
            ->selectRaw('valor, count(*) as total')->groupBy('valor')->orderByDesc('total')->value('valor');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
