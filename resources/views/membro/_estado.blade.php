{{-- Autor: Leonardo (leonardoaderito-cmyk) - etiqueta do estado de uma quota. Espera $q. --}}
@php
    $rotulosQuota = ['pendente' => 'Por pagar', 'paga' => 'Paga', 'em_atraso' => 'Em atraso', 'isenta' => 'Isenta'];
    $coresQuota = ['pendente' => 'aviso', 'paga' => 'ligado', 'em_atraso' => 'alerta', 'isenta' => 'neutro'];
@endphp
@if ($q->talao_estado === 'em_validacao' && ! in_array($q->estado, ['paga', 'isenta'], true))
    <span class="admin-estado neutro">Talão em validação</span>
@else
    <span class="admin-estado {{ $coresQuota[$q->estado] ?? 'neutro' }}">{{ $rotulosQuota[$q->estado] ?? $q->estado }}</span>
@endif
