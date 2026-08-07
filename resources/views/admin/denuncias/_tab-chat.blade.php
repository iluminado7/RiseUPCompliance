<div class="detail-card" id="chat">
    <h3>💬 Chat con el denunciante</h3>

    @if ($mensajes->isEmpty())
        <p style="color:#aaa;font-size:13px;margin-bottom:16px;">
            No hay mensajes aún.
            @if ($puedeChatear)
                Iniciá la conversación enviando el primer mensaje.
            @else
                Esta denuncia es anónima: el denunciante no dejó vía de contacto.
            @endif
        </p>
    @else
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;max-height:400px;overflow-y:auto;padding:4px 0;">
            @foreach ($mensajes as $mensaje)
                @php
                    $esAnalista = $mensaje['es_analista'];
                @endphp

                <div style="display:flex;flex-direction:column;align-items:{{ $esAnalista ? 'flex-end' : 'flex-start' }};">
                    <div style="max-width:75%;background:{{ $esAnalista ? '#1a1710' : '#f0f0f0' }};color:{{ $esAnalista ? '#fff' : '#333' }};padding:10px 14px;border-radius:{{ $esAnalista ? '12px 12px 2px 12px' : '12px 12px 12px 2px' }};font-size:14px;line-height:1.5;">
                        @if ($mensaje['texto'] === null)
                            <em style="opacity:.7;">No se pudo descifrar este mensaje.</em>
                        @else
                            {!! nl2br(e($mensaje['texto'])) !!}
                        @endif
                    </div>

                    <div style="font-size:11px;color:#aaa;margin-top:3px;">
                        {{ $mensaje['autor'] }} ·
                        {{ $mensaje['fecha']->format('d/m H:i') }}
                        @if ($esAnalista)
                            ·
                            @if ($mensaje['leido'])
                                <span style="color:#4caf50;">✓✓ Visto</span>
                            @else
                                <span style="color:#BFAE76;">✓ Enviado</span>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($puedeChatear)
        <form method="POST" action="{{ route('admin.denuncias.mensaje', $denuncia) }}"
              style="display:flex;gap:8px;align-items:flex-end;margin-top:8px;">
            @csrf
            <input type="hidden" name="volver" value="{{ $volverCrudo }}">

            <textarea name="contenido" rows="2" maxlength="2000" required
                      placeholder="Escribí tu mensaje al denunciante..."
                      style="flex:1;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:14px;outline:none;font-family:inherit;resize:vertical;"></textarea>

            <button type="submit" class="btn btn-primary" style="padding:10px 18px;white-space:nowrap;">
                Enviar
            </button>
        </form>

        <div style="font-size:11px;color:#aaa;margin-top:6px;">
            El denunciante verá este mensaje al consultar su código de seguimiento.
        </div>
    @endif
</div>
