<x-admin.layout titulo="Configuración global">

    <style>
        .cg-grupo { margin-bottom:20px; }
        .cg-fila {
            display:grid; grid-template-columns:minmax(220px,1fr) minmax(200px,280px);
            gap:20px; align-items:start; padding:16px 0;
            border-bottom:1px solid var(--border);
        }
        .cg-fila:last-child { border-bottom:none; }
        .cg-etiqueta { font-size:14px; font-weight:700; color:var(--dark); margin-bottom:4px; }
        .cg-desc { font-size:12px; color:var(--text-light); line-height:1.5; }
        .cg-nota {
            margin-top:8px; padding:8px 10px; background:#fff8e1;
            border-left:3px solid var(--gold); border-radius:4px;
            font-size:11px; color:#856404; line-height:1.5;
        }
        .cg-meta { margin-top:6px; font-size:11px; color:#aaa; }
        .cg-campo input, .cg-campo select {
            width:100%; padding:9px 12px; border:1px solid var(--border);
            border-radius:8px; font-size:13px; font-family:inherit; outline:none;
            background:#fff;
        }
        .cg-campo input:focus, .cg-campo select:focus { border-color:var(--gold); }
        .cg-error { margin-top:5px; font-size:11px; color:#b00020; font-weight:600; }
        .cg-solo-lectura { font-family:monospace; font-size:12px; }
        @media (max-width:768px) { .cg-fila { grid-template-columns:1fr; gap:8px; } }
    </style>

    <div style="padding:12px 16px;background:#e8f0fe;border:1px solid #c6d9f7;border-radius:10px;font-size:13px;color:#1a5fb4;margin-bottom:20px;">
        Estos valores se aplican a las <strong>empresas que se creen de ahora en
        adelante</strong>. Las empresas ya existentes conservan su propia
        configuración y no se ven afectadas.
    </div>

    <form method="POST" action="{{ route('admin.config-global.guardar') }}">
        @csrf
        @method('PUT')

        @foreach ($grupos as $nombreGrupo => $definiciones)
            <div class="card cg-grupo">
                <div class="card-header">
                    <span class="card-title">{{ $nombreGrupo }}</span>
                </div>

                <div style="padding:4px 24px 20px;">
                    @foreach ($definiciones as $def)
                        @php
                            $campo = $def->campo();
                            $valor = old('config.' . $campo, $valores[$def->clave] ?? $def->porDefecto);
                            $meta = $metadatos[$def->clave] ?? null;
                        @endphp

                        <div class="cg-fila">
                            <div>
                                <div class="cg-etiqueta">{{ $def->etiqueta }}</div>
                                <div class="cg-desc">{{ $def->descripcion }}</div>

                                @if ($def->nota)
                                    <div class="cg-nota">{{ $def->nota }}</div>
                                @endif

                                <div class="cg-meta">
                                    <code>{{ $def->clave }}</code>
                                    @if ($meta)
                                        · v{{ $meta->version }}
                                        · {{ $meta->updated_at?->format('d/m/Y H:i') ?? '—' }}
                                        @if ($meta->actualizadoPor)
                                            · {{ $meta->actualizadoPor->name ?? '—' }}
                                        @endif
                                    @else
                                        · sin fila en la base (se usa el valor por defecto)
                                    @endif
                                </div>
                            </div>

                            <div class="cg-campo">
                                @if ($def->opciones)
                                    <select name="config[{{ $campo }}]" id="{{ $campo }}">
                                        @foreach ($def->opciones as $clave => $etiqueta)
                                            <option value="{{ $clave }}" @selected((string) $valor === (string) $clave)>
                                                {{ $etiqueta }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif ($def->tipo === 'integer')
                                    <input type="number" name="config[{{ $campo }}]" id="{{ $campo }}"
                                           value="{{ $valor }}" min="1" step="1">
                                @elseif ($def->esSecreta)
                                    <input type="password" name="config[{{ $campo }}]" id="{{ $campo }}"
                                           value="" placeholder="Sin cambios" autocomplete="new-password">
                                @else
                                    <input type="text" name="config[{{ $campo }}]" id="{{ $campo }}"
                                           value="{{ $valor }}">
                                @endif

                                @error('config.' . $campo)
                                    <div class="cg-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div style="display:flex;gap:10px;align-items:center;margin-bottom:24px;">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <span style="font-size:12px;color:var(--text-light);">
                Cada cambio queda registrado en la traza de auditoría.
            </span>
        </div>
    </form>

    @if (count($noDeclaradas))
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    Claves no declaradas
                    <span style="font-size:12px;font-weight:400;color:var(--text-light);margin-left:8px;">
                        ({{ count($noDeclaradas) }})
                    </span>
                </span>
            </div>

            <div style="padding:0 24px 20px;">
                <p style="font-size:12px;color:var(--text-light);margin-bottom:14px;line-height:1.6;">
                    Existen en <code>global_config</code> pero no están en el catálogo
                    del sistema, así que no se editan desde acá: sin saber qué código las
                    consume, cambiarlas sería escribir a ciegas. Para hacerlas editables
                    hay que declararlas en <code>CatalogoConfigGlobal</code>.
                </p>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Valor</th>
                                <th>Tipo</th>
                                <th>Versión</th>
                                <th>Modificada</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($noDeclaradas as $fila)
                                <tr>
                                    <td class="cg-solo-lectura">{{ $fila->key }}</td>
                                    <td class="cg-solo-lectura">
                                        {{ $fila->is_secret ? '••••••••' : ($fila->value ?? '—') }}
                                    </td>
                                    <td>{{ $fila->value_type }}</td>
                                    <td>v{{ $fila->version }}</td>
                                    <td>{{ $fila->updated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</x-admin.layout>
