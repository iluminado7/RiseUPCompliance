<x-publico.layout titulo="Estado de tu denuncia">

    @if (session('estado'))
        <div class="cn-ok">{{ session('estado') }}</div>
    @endif

    <div class="cn-card">
        <h2>Estado de tu denuncia</h2>

        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:14px;">
            <span style="color:var(--text-light);">Estado</span>
            <strong>{{ $denuncia->status->estadoPublico() }}</strong>
        </div>

        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:14px;">
            <span style="color:var(--text-light);">Fecha de la denuncia</span>
            <span>{{ $denuncia->created_at->format('d/m/Y') }}</span>
        </div>

        <div style="display:flex;justify-content:space-between;padding:10px 0;font-size:14px;">
            <span style="color:var(--text-light);">Última actualización</span>
            <span>{{ $denuncia->last_action_at?->format('d/m/Y') ?? '—' }}</span>
        </div>

        {{-- No se muestra a quién se denunció ni en qué sector: quien tiene
             el código no es necesariamente el denunciante, y esa información
             puede poner a alguien en riesgo. --}}
    </div>

    @if ($denuncia->chat_enabled)
        <div class="cn-card">
            <h2>Mensajes</h2>
            <p class="ayuda">
                El equipo que investiga puede escribirte por acá si necesita
                más información.
            </p>

            @if ($mensajes->isEmpty())
                <p style="font-size:13px;color:#aaa;">Todavía no hay mensajes.</p>
            @else
                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
                    @foreach ($mensajes as $mensaje)
                        <div style="display:flex;flex-direction:column;align-items:{{ $mensaje['de_equipo'] ? 'flex-start' : 'flex-end' }};">
                            <div style="max-width:78%;background:{{ $mensaje['de_equipo'] ? '#f0f0f0' : 'var(--gold)' }};color:{{ $mensaje['de_equipo'] ? '#333' : 'var(--dark)' }};padding:10px 14px;border-radius:12px;font-size:14px;">
                                @if ($mensaje['texto'] === null)
                                    <em style="opacity:.7;">No se pudo mostrar este mensaje.</em>
                                @else
                                    {!! nl2br(e($mensaje['texto'])) !!}
                                @endif
                            </div>
                            <div style="font-size:11px;color:#aaa;margin-top:3px;">
                                {{ $mensaje['de_equipo'] ? 'El equipo' : 'Vos' }} ·
                                {{ $mensaje['fecha']->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($puedeEscribir)
                <form method="POST" action="{{ route('seguimiento.responder') }}"
                      style="display:flex;gap:8px;align-items:flex-end;">
                    @csrf
                    <textarea name="contenido" rows="2" maxlength="2000" required
                              placeholder="Escribí tu respuesta..."
                              style="flex:1;padding:10px 13px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;"></textarea>
                    <button type="submit" class="cn-btn cn-btn-principal">Enviar</button>
                </form>
            @elseif ($denuncia->is_anonymous)
                <p style="font-size:12px;color:var(--text-light);">
                    Como denunciaste de forma anónima, podés leer los mensajes pero
                    no responder.
                </p>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('seguimiento.salir') }}">
        @csrf
        <button type="submit" class="cn-btn cn-btn-secundario">Cerrar consulta</button>
    </form>

    <p style="font-size:12px;color:var(--text-light);margin-top:12px;">
        Cerrá la consulta si estás en una computadora compartida.
    </p>

</x-publico.layout>
