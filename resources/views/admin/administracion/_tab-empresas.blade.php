<div class="card">
    <div class="card-header">
        <span class="card-title">
            {{ $empresas->count() }} {{ \Illuminate\Support\Str::plural('empresa', $empresas->count()) }}
        </span>
        <a href="{{ route('admin.empresas.create') }}" class="btn btn-primary btn-sm">+ Nueva empresa</a>
    </div>

    <div class="table-wrapper">
        @if ($empresas->isEmpty())
            <div class="empty-state">
                <div class="icon">🏢</div>
                <p>No hay empresas registradas.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Canal público</th>
                        <th>Estado</th>
                        <th>Responsable</th>
                        <th>Denuncias</th>
                        <th>Alta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empresas as $empresa)
                        @php
                            [$claseEstado, $etiquetaEstado] = match ($empresa->status->value) {
                                'active' => ['badge-activo', 'ACTIVA'],
                                'suspended' => ['badge-suspendido', 'SUSPENDIDA'],
                                default => ['badge-inactivo', 'INACTIVA'],
                            };
                        @endphp

                        <tr>
                            <td><strong>{{ $empresa->name }}</strong></td>
                            <td>{{ $empresa->email }}</td>
                            <td style="color:#888;font-size:12px;">/{{ $empresa->slug }}</td>
                            <td><span class="{{ $claseEstado }}">{{ $etiquetaEstado }}</span></td>
                            <td>{{ $empresa->managerPlataforma?->full_name ?? '—' }}</td>
                            <td>{{ $empresa->denuncias_count }}</td>
                            <td>{{ $empresa->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                    <a href="{{ route('admin.empresas.edit', $empresa) }}"
                                       class="btn btn-primary btn-sm">Editar</a>

                                    {{-- Cada cambio de estado es su propio formulario POST con
                                         confirmacion. El original usaba un menu con JS que
                                         disparaba un modal; el resultado es el mismo y aca no
                                         hay estado en el navegador que pueda desincronizarse. --}}
                                    @if ($empresa->status->value !== 'suspended')
                                        <form method="POST" action="{{ route('admin.empresas.estado', $empresa) }}"
                                              onsubmit="return confirm('¿Suspender {{ $empresa->name }}? Su canal público deja de recibir denuncias, pero sus usuarios siguen operando los casos abiertos.');">
                                            @csrf
                                            <input type="hidden" name="status" value="suspended">
                                            <button type="submit" class="btn btn-secondary btn-sm">Suspender</button>
                                        </form>
                                    @endif

                                    @if ($empresa->status->value !== 'deactivated')
                                        <form method="POST" action="{{ route('admin.empresas.estado', $empresa) }}"
                                              onsubmit="return confirm('¿Desactivar {{ $empresa->name }}? Todos sus usuarios pierden el acceso al panel de inmediato.');">
                                            @csrf
                                            <input type="hidden" name="status" value="deactivated">
                                            <button type="submit" class="btn btn-secondary btn-sm">Desactivar</button>
                                        </form>
                                    @endif

                                    @if ($empresa->status->value !== 'active')
                                        <form method="POST" action="{{ route('admin.empresas.estado', $empresa) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="btn btn-secondary btn-sm">Reactivar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
