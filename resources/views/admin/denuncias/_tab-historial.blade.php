@php
    // Nombres completos en vez de `use`: los imports no pueden ir dentro
    // de un bloque @php, porque Blade lo compila como cuerpo de funcion.
    $etiquetaEstado = function (?string $valor): string {
        if (! $valor) {
            return '';
        }
        // from_status y to_status son varchar y no enum a proposito: el
        // historial conserva estados que quiza ya no existan.
        return \App\Enums\EstadoDenuncia::tryFrom($valor)?->etiqueta() ?? $valor;
    };

    $etiquetaPrioridad = function (?string $valor): string {
        if (! $valor) {
            return 'Sin prioridad';
        }
        return \App\Enums\PrioridadDenuncia::tryFrom($valor)?->etiqueta() ?? $valor;
    };
@endphp

<div class="detail-card">
    <h3>Historial del caso</h3>

    @if ($historial->isEmpty())
        <p style="color:#aaa;font-size:13px;">Sin historial registrado.</p>
    @else
        <div style="display:flex;flex-direction:column;">
            @foreach ($historial as $item)
                @php
                    $payload = $item['payload'] ?? [];

                    if ($item['tipo'] === 'estado') {
                        $icono = '🔄';
                    } elseif ($item['tipo'] === 'priority_changed') {
                        $icono = '⚡';
                    } elseif ($item['tipo'] === 'assigned') {
                        $icono = '👤';
                    } else {
                        $icono = '📋';
                    }
                @endphp

                <div style="padding:12px 0;border-bottom:1px solid #f5f5f5;font-size:13px;display:flex;gap:14px;align-items:flex-start;">
                    <div style="font-size:16px;margin-top:1px;flex-shrink:0;">{{ $icono }}</div>

                    <div style="flex:1;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:3px;flex-wrap:wrap;gap:6px;">
                            <span>
                                @if ($item['tipo'] === 'estado')
                                    @if ($item['desde'])
                                        <span style="color:#aaa">{{ $etiquetaEstado($item['desde']) }}</span>
                                        → <strong>{{ $etiquetaEstado($item['hasta']) }}</strong>
                                    @else
                                        Estado inicial: <strong>{{ $etiquetaEstado($item['hasta']) }}</strong>
                                    @endif

                                @elseif ($item['tipo'] === 'priority_changed')
                                    Prioridad:
                                    <span style="color:#aaa">{{ $etiquetaPrioridad($payload['from'] ?? null) }}</span>
                                    → <strong>{{ $etiquetaPrioridad($payload['to'] ?? null) }}</strong>

                                @elseif ($item['tipo'] === 'assigned')
                                    @if (! empty($payload['asignados']))
                                        Asignados:
                                        <strong>{{ implode(', ', $payload['asignados']) }}</strong>
                                    @endif
                                    @if (! empty($payload['desasignados']))
                                        @if (! empty($payload['asignados'])) · @endif
                                        Removidos:
                                        <span style="color:#aaa">{{ implode(', ', $payload['desasignados']) }}</span>
                                    @endif
                                    @if (empty($payload['asignados']) && empty($payload['desasignados']))
                                        Asignación actualizada
                                    @endif

                                @else
                                    {{ $item['tipo'] }}
                                @endif
                            </span>

                            <span style="color:#aaa;font-size:12px;">
                                {{ $item['fecha']->format('d/m/Y H:i') }}
                            </span>
                        </div>

                        <div style="color:#888;font-size:12px;">
                            Por {{ $item['usuario'] ?? 'Sistema' }}
                        </div>

                        @if (! empty($item['razon']))
                            <div style="color:#555;margin-top:3px;font-size:12px;font-style:italic;">
                                "{{ $item['razon'] }}"
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
