<div class="detail-grid">

    <div style="display:flex;flex-direction:column;gap:20px;">

        <div class="detail-card">
            <h3>Datos generales</h3>

            <div class="detail-row">
                <span class="detail-label">Empresa</span>
                <span class="detail-value">{{ $denuncia->empresa->name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Sucursal</span>
                <span class="detail-value">{{ $denuncia->sucursal?->name ?? '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Categoría</span>
                <span class="detail-value">{{ $denuncia->categoria?->name_es ?? '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Canal</span>
                <span class="detail-value">{{ ucfirst($denuncia->intake_channel) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha del hecho</span>
                <span class="detail-value">{{ $denuncia->incident_date?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha de ingreso</span>
                <span class="detail-value">{{ $denuncia->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Anonimato</span>
                <span class="detail-value">
                    {{ $denuncia->is_anonymous ? '🕵️ Anónima' : 'Identificada' }}
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Asignado a</span>
                <span class="detail-value">
                    @forelse ($denuncia->asignaciones as $asignacion)
                        {{ $asignacion->asignadoA->nombreCompleto() }}@if (! $loop->last), @endif
                    @empty
                        <span style="color:#aaa">Sin asignar</span>
                    @endforelse
                </span>
            </div>
        </div>

        <div class="detail-card">
            <h3>Persona denunciada</h3>
            <div class="detail-row">
                <span class="detail-label">Nombre</span>
                <span class="detail-value">{{ $descifrados['denunciado_nombre'] ?? '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Apellido</span>
                <span class="detail-value">{{ $descifrados['denunciado_apellido'] ?? '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Sector</span>
                <span class="detail-value">
                    {{ $denuncia->area?->name_es ?? $denuncia->reported_area_other ?? '—' }}
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Cargo</span>
                <span class="detail-value">
                    {{ $denuncia->cargo?->name_es ?? $denuncia->reported_position_other ?? '—' }}
                </span>
            </div>
        </div>

        @if ($denuncia->is_anonymous)
            <div class="detail-card">
                <h3>Datos del denunciante</h3>
                <div style="text-align:center;padding:16px;color:var(--text-light);font-size:13px;">
                    🕵️ Denuncia anónima — los datos del denunciante no fueron registrados
                </div>
            </div>
        @elseif (! $puedeVerDenunciante)
            <div class="detail-card">
                <h3>Datos del denunciante</h3>
                <div style="text-align:center;padding:16px;color:var(--text-light);font-size:13px;">
                    🔒 Tu rol no tiene acceso a la identidad del denunciante
                </div>
            </div>
        @else
            <div class="detail-card" style="border-left:3px solid var(--gold);">
                <h3>Datos del denunciante</h3>
                <div class="detail-row">
                    <span class="detail-label">Nombre</span>
                    <span class="detail-value">{{ $descifrados['reportero_nombre'] ?? '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Apellido</span>
                    <span class="detail-value">{{ $descifrados['reportero_apellido'] ?? '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">{{ $descifrados['reportero_email'] ?? '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Relación con la empresa</span>
                    <span class="detail-value">
                        {{ $denuncia->vinculo?->name_es ?? $denuncia->relationship_other_text ?? '—' }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Teléfono</span>
                    <span class="detail-value">{{ $descifrados['reportero_telefono'] ?? '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">DNI / ID</span>
                    <span class="detail-value">{{ $descifrados['reportero_documento'] ?? '—' }}</span>
                </div>
            </div>
        @endif

        @if ($archivo)
            @php
                $etiquetasScan = [
                    'clean' => '✓ Limpio',
                    'infected' => '✗ Infectado',
                    'pending' => '⏳ Pendiente',
                    'error' => '⚠ Error',
                ];
                $tamanio = $archivo->size_bytes;
                $unidades = ['B', 'KB', 'MB', 'GB'];
                $i = 0;
                while ($tamanio >= 1024 && $i < count($unidades) - 1) {
                    $tamanio /= 1024;
                    $i++;
                }
            @endphp

            <div class="detail-card">
                <h3>Archivo adjunto</h3>
                <div class="detail-row">
                    <span class="detail-label">Nombre</span>
                    <span class="detail-value">{{ $archivo->original_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tamaño</span>
                    <span class="detail-value">{{ round($tamanio, 1) }} {{ $unidades[$i] }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tipo</span>
                    <span class="detail-value">{{ $archivo->detected_mime ?? '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Antivirus</span>
                    <span class="detail-value">
                        {{ $etiquetasScan[$archivo->scan_result] ?? $archivo->scan_result }}
                    </span>
                </div>
                @if ($archivo->clean_metadata)
                    <div class="detail-row">
                        <span class="detail-label">Metadatos</span>
                        <span class="detail-value" style="font-size:12px;color:var(--text-light);">
                            Depurados al subir
                        </span>
                    </div>
                @endif
            </div>
        @endif

    </div>

    <div style="display:flex;flex-direction:column;gap:20px;">
        @include('admin.denuncias._acciones')
    </div>

</div>

@if ($respuestas->isNotEmpty())
    <div class="detail-card" style="margin-top:20px;">
        <h3>Respuestas del cuestionario</h3>
        @foreach ($respuestas as $item)
            <div class="answer-block">
                <div class="answer-question">{{ $item['pregunta'] }}</div>
                <div class="answer-text">{!! nl2br(e($item['respuesta'] ?? '—')) !!}</div>
            </div>
        @endforeach
    </div>
@endif
