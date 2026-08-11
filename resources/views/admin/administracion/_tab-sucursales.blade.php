<div class="card">
    <div class="card-header">
        <span class="card-title">
            {{ $sucursales->count() }} {{ \Illuminate\Support\Str::plural('sucursal', $sucursales->count()) }}
        </span>
        <a href="{{ route('admin.sucursales.create') }}" class="btn btn-primary btn-sm">+ Nueva sucursal</a>
    </div>

    <div class="table-wrapper">
        @if ($sucursales->isEmpty())
            <div class="empty-state">
                <div class="icon">🏬</div>
                <p>No hay sucursales registradas.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        @if ($esSuperadmin)
                            <th>Empresa</th>
                        @endif
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Dirección</th>
                        <th>Sede central</th>
                        <th>Estado</th>
                        <th>Denuncias</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sucursales as $sucursal)
                        <tr>
                            @if ($esSuperadmin)
                                <td>{{ $sucursal->empresa->name }}</td>
                            @endif
                            <td><strong>{{ $sucursal->name }}</strong></td>
                            <td>{{ $sucursal->internal_code ?? '—' }}</td>
                            <td style="font-size:12px;color:var(--text-light);">
                                {{ $sucursal->address ?? '—' }}
                            </td>
                            <td>{{ $sucursal->is_headquarter ? '★ Sí' : '—' }}</td>
                            <td>
                                @if ($sucursal->is_active)
                                    <span class="badge-activo">ACTIVA</span>
                                @else
                                    <span class="badge-inactivo">INACTIVA</span>
                                @endif
                            </td>
                            <td>{{ $sucursal->denuncias_count }}</td>
                            <td class="col-accion-ver">
                                <a href="{{ route('admin.sucursales.edit', $sucursal) }}"
                                   class="btn btn-primary btn-sm">Editar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
