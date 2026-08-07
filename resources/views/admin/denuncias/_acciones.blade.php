{{-- Columna derecha de la pestana Informacion: acciones sobre el caso --}}

@if ($puedeCambiarEstado)
    <div class="action-card">
        <h3>Cambiar estado</h3>

        @if (empty($transiciones))
            <p style="color:var(--text-light);font-size:13px;">
                La denuncia está archivada. No admite más cambios de estado.
            </p>
        @else
            <form method="POST" action="{{ route('admin.denuncias.estado', $denuncia) }}">
                @csrf
                <input type="hidden" name="volver" value="{{ $volverCrudo }}">

                {{-- Solo las transiciones validas desde el estado actual.
                     El original ofrecia siempre la misma lista fija y no
                     validaba nada del lado del servidor. --}}
                <select name="nuevo_estado" required>
                    @foreach ($transiciones as $destino)
                        <option value="{{ $destino->value }}">{{ $destino->etiqueta() }}</option>
                    @endforeach
                </select>

                <textarea name="razon" rows="3" maxlength="1000"
                          placeholder="Motivo del cambio (opcional)..."></textarea>

                <button type="submit" class="btn btn-primary">Actualizar estado</button>
            </form>
        @endif
    </div>
@endif

@if ($puedeAsignar && $analistas->isNotEmpty())
    <div class="action-card">
        <h3>Gestores asignados</h3>

        <form method="POST" action="{{ route('admin.denuncias.asignar', $denuncia) }}">
            @csrf
            <input type="hidden" name="volver" value="{{ $volverCrudo }}">

            @if ($denuncia->asignaciones->isNotEmpty())
                <div style="margin-bottom:12px;padding:10px 12px;background:#f0f7f0;border:1px solid #c3e6cb;border-radius:8px;">
                    <div style="font-size:11px;font-weight:700;color:#155724;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">
                        Asignados actualmente
                    </div>
                    @foreach ($denuncia->asignaciones as $asignacion)
                        <div style="font-size:13px;color:#155724;">
                            ✓ {{ $asignacion->asignadoA->nombreCompleto() }}
                            <span style="font-size:11px;color:#888;margin-left:4px;">
                                ({{ $asignacion->asignadoA->nombreRol()?->etiqueta() }})
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="font-size:13px;color:#aaa;margin-bottom:12px;font-style:italic;">
                    Sin gestores asignados
                </div>
            @endif

            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;max-height:200px;overflow-y:auto;padding:2px;">
                @foreach ($analistas as $analista)
                    <label style="display:flex;align-items:center;gap:10px;font-size:13px;cursor:pointer;padding:6px 8px;border-radius:6px;">
                        <input type="checkbox" name="analista_ids[]" value="{{ $analista->id }}"
                               @checked(in_array($analista->id, $asignadosIds))
                               style="width:16px;height:16px;accent-color:var(--gold);">
                        <span>
                            {{ $analista->nombreCompleto() }}
                            <span style="font-size:11px;color:#999;margin-left:4px;">
                                ({{ $analista->nombreRol()?->etiqueta() }})
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div style="margin-bottom:10px;">
                <input type="text" name="razon_asignacion" maxlength="500"
                       placeholder="Motivo de la asignación (opcional)"
                       style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;outline:none;">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Guardar asignación</button>
        </form>
    </div>
@endif

@if ($puedeAsignar)
    <div class="action-card">
        <h3>Prioridad</h3>

        <form method="POST" action="{{ route('admin.denuncias.prioridad', $denuncia) }}">
            @csrf
            <input type="hidden" name="volver" value="{{ $volverCrudo }}">

            <select name="nueva_prioridad" required>
                @foreach (\App\Enums\PrioridadDenuncia::cases() as $opcion)
                    <option value="{{ $opcion->value }}" @selected($denuncia->priority === $opcion)>
                        {{ $opcion->etiqueta() }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                Guardar prioridad
            </button>
        </form>
    </div>
@endif
