@if ($puedeCrearNota)
    <div class="detail-card" style="margin-bottom:20px;">
        <h3>Nueva nota interna</h3>

        <form method="POST" action="{{ route('admin.denuncias.notas.crear', $denuncia) }}">
            @csrf
            <input type="hidden" name="volver" value="{{ $volverCrudo }}">

            <textarea name="contenido" rows="4" maxlength="5000" required
                      placeholder="Escribí la nota interna... Solo visible para el equipo de investigación."
                      style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;outline:none;"></textarea>

            <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin:10px 0;cursor:pointer;">
                <input type="checkbox" name="es_prioritaria" value="1"
                       style="width:16px;height:16px;accent-color:var(--gold);">
                Marcar como prioritaria
            </label>

            <button type="submit" class="btn btn-primary">Guardar nota</button>
        </form>
    </div>
@endif

<div class="detail-card">
    <h3>Notas internas</h3>

    @if ($notas->isEmpty())
        <p style="color:#aaa;font-size:13px;">Todavía no hay notas en este caso.</p>
    @else
        @foreach ($notas as $nota)
            @php
                $eliminada = $nota->deleted_at !== null;
            @endphp

            <div class="nota-card {{ $nota->is_priority ? 'prioritaria' : '' }} {{ $eliminada ? 'eliminada' : '' }}"
                 style="background:var(--white);border:1px solid var(--border);border-radius:10px;padding:16px 18px;margin-bottom:12px;position:relative;{{ $nota->is_priority ? 'border-left:3px solid var(--gold);' : '' }}{{ $eliminada ? 'opacity:.55;background:#fafafa;' : '' }}">

                <div class="nota-content"
                     style="font-size:14px;line-height:1.5;{{ $eliminada ? 'text-decoration:line-through;color:#aaa;' : '' }}">
                    {!! nl2br(e($nota->content)) !!}
                </div>

                <div style="font-size:11px;color:var(--text-light);margin-top:10px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                    <span>{{ $nota->autor->nombreCompleto() }}</span>
                    <span>{{ $nota->created_at->format('d/m/Y H:i') }}</span>

                    @if ($nota->fueEditada())
                        <span title="Editada el {{ $nota->edited_at->format('d/m/Y H:i') }}">
                            ✎ editada por {{ $nota->editadaPor?->nombreCompleto() ?? '—' }}
                        </span>
                    @endif

                    @if ($eliminada)
                        <span>🗑 eliminada por {{ $nota->eliminadaPor?->nombreCompleto() ?? '—' }}</span>
                    @endif
                </div>

                @unless ($eliminada)
                    @can('update', $nota)
                        <div style="display:flex;gap:6px;margin-top:10px;">
                            <button type="button" class="btn btn-secondary btn-sm"
                                    onclick="editarNota({{ $nota->id }})">Editar</button>

                            <form method="POST" action="{{ route('admin.denuncias.notas.eliminar', [$denuncia, $nota]) }}"
                                  onsubmit="return confirm('¿Eliminar esta nota? Queda registrada como eliminada, no se borra del historial.');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="volver" value="{{ $volverCrudo }}">
                                <button type="submit" class="btn btn-secondary btn-sm">Eliminar</button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('admin.denuncias.notas.editar', [$denuncia, $nota]) }}"
                              id="form-editar-{{ $nota->id }}" style="display:none;margin-top:12px;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="volver" value="{{ $volverCrudo }}">

                            <textarea name="contenido" rows="4" maxlength="5000" required
                                      style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;">{{ $nota->content }}</textarea>

                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin:10px 0;cursor:pointer;">
                                <input type="checkbox" name="es_prioritaria" value="1" @checked($nota->is_priority)
                                       style="width:16px;height:16px;accent-color:var(--gold);">
                                Marcar como prioritaria
                            </label>

                            <div style="display:flex;gap:6px;">
                                <button type="submit" class="btn btn-primary btn-sm">Guardar cambios</button>
                                <button type="button" class="btn btn-secondary btn-sm"
                                        onclick="editarNota({{ $nota->id }})">Cancelar</button>
                            </div>
                        </form>
                    @endcan
                @endunless
            </div>
        @endforeach
    @endif
</div>

<script>
    function editarNota(id) {
        const form = document.getElementById('form-editar-' + id);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
</script>
