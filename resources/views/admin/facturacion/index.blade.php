<x-admin.layout titulo="Facturación">

    @php
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $estados = [
            'pending' => ['badge-suspendido', 'Pendiente'],
            'paid' => ['badge-activo', 'Pagada'],
            'overdue' => ['badge-inactivo', 'Vencida'],
            'cancelled' => ['badge-inactivo', 'Anulada'],
        ];

        $plata = fn ($monto, $moneda = 'USD') => $moneda . ' ' . number_format((float) $monto, 2, ',', '.');
    @endphp

    <style>
        .fc-resumen { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
        .fc-tarjeta {
            background:var(--white); border-radius:var(--radius); padding:20px;
            box-shadow:var(--shadow);
        }
        .fc-tarjeta .label { font-size:12px; color:var(--text-light); margin-bottom:6px; }
        .fc-tarjeta .valor { font-size:26px; font-weight:700; color:var(--dark); line-height:1.1; }
        .fc-tarjeta .sub { font-size:11px; color:var(--text-light); margin-top:5px; }

        .fc-form { background:#fafafa; border-top:1px solid var(--border); padding:18px 20px; }
        .fc-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:14px; }
        .fc-grid .ancho { grid-column:1 / -1; }
        .campo { display:flex; flex-direction:column; gap:5px; }
        .campo label { font-size:12px; font-weight:700; color:var(--text-light); text-transform:uppercase; letter-spacing:.04em; }
        .campo input, .campo select, .campo textarea {
            padding:9px 12px; border:1px solid var(--border); border-radius:8px;
            font-size:13px; font-family:inherit; outline:none;
        }
        .campo input:focus, .campo select:focus { border-color:var(--gold); }
        .fila-accion { display:none; background:#fffbe6; }

        @media (max-width:900px) {
            .fc-resumen { grid-template-columns:repeat(2,1fr); }
            .fc-grid { grid-template-columns:1fr; }
        }
        @media (max-width:560px) { .fc-resumen { grid-template-columns:1fr; } }
    </style>

    {{-- ── Selector de empresa ── --}}
    @if ($esSuperadmin)
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><span class="card-title">Empresa</span></div>
            <div style="padding:16px 20px;">
                <form method="GET" action="{{ route('admin.facturacion.index') }}"
                      style="display:flex;gap:10px;flex-wrap:wrap;">
                    <x-autocompletado
                        nombre="empresa_nombre"
                        nombre-oculto="empresa_id"
                        :valor="$empresa?->name"
                        :valor-oculto="$empresa?->id"
                        :opciones="$empresas->pluck('name')"
                        :valores="$empresas->pluck('id', 'name')"
                        :enviar-al-elegir="true"
                        placeholder="Todas las empresas" />

                    @if ($empresa)
                        <a href="{{ route('admin.facturacion.index') }}"
                           class="btn btn-secondary btn-sm">Ver todas</a>
                    @endif
                </form>
            </div>
        </div>
    @endif

    {{-- ── Resumen ── --}}
    <div class="fc-resumen">
        <div class="fc-tarjeta">
            <div class="label">Pendientes</div>
            <div class="valor">{{ $resumen['pendientes'] }}</div>
            <div class="sub">Aún no vencidas</div>
        </div>

        <div class="fc-tarjeta">
            <div class="label">Vencidas</div>
            <div class="valor" style="color:{{ $resumen['vencidas'] ? '#b00020' : '#28a745' }};">
                {{ $resumen['vencidas'] }}
            </div>
            <div class="sub">{{ $resumen['vencidas'] ? 'Requieren gestión' : 'Sin vencidas' }}</div>
        </div>

        <div class="fc-tarjeta">
            <div class="label">Adeudado</div>
            <div class="valor" style="font-size:21px;">{{ $plata($resumen['adeudado']) }}</div>
            <div class="sub">Pendientes + vencidas</div>
        </div>

        <div class="fc-tarjeta">
            <div class="label">Cobrado {{ now()->year }}</div>
            <div class="valor" style="font-size:21px;color:#28a745;">{{ $plata($resumen['cobrado_anio']) }}</div>
            <div class="sub">Facturas pagadas</div>
        </div>
    </div>

    {{-- ── Datos fiscales, en modo lectura ── --}}
    @if ($empresa)
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span class="card-title">Datos fiscales de {{ $empresa->name }}</span>
                @if ($esSuperadmin)
                    <a href="{{ route('admin.empresas.edit', $empresa) }}" class="btn btn-secondary btn-sm">
                        Editar en Administración
                    </a>
                @endif
            </div>

            <div style="padding:16px 20px;">
                @if ($datosFiscales)
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;font-size:13px;">
                        <div>
                            <div style="color:var(--text-light);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">CUIT</div>
                            <div>{{ $datosFiscales->tax_id ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="color:var(--text-light);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Razón social</div>
                            <div>{{ $datosFiscales->legal_name ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="color:var(--text-light);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Condición IVA</div>
                            <div>{{ $datosFiscales->vat_status ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="color:var(--text-light);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Forma de pago</div>
                            <div>
                                {{ match ($datosFiscales->preferred_payment) {
                                    'bank_transfer' => 'Transferencia bancaria',
                                    'debit' => 'Débito automático',
                                    'check' => 'Cheque',
                                    default => '—',
                                } }}
                            </div>
                        </div>
                        <div>
                            <div style="color:var(--text-light);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Día de facturación</div>
                            <div>{{ $datosFiscales->billing_day ?? '—' }}</div>
                        </div>
                        <div>
                            <div style="color:var(--text-light);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Emails</div>
                            <div style="font-size:12px;">
                                {{ $datosFiscales->billing_emails ? implode(', ', $datosFiscales->billing_emails) : '—' }}
                            </div>
                        </div>
                    </div>
                @else
                    <p style="font-size:13px;color:var(--text-light);">
                        Esta empresa no tiene datos fiscales cargados.
                        @if ($esSuperadmin)
                            Completalos desde Administración antes de facturar.
                        @endif
                    </p>
                @endif
            </div>
        </div>
    @endif

    {{-- ── Alta de factura ── --}}
    @if ($esSuperadmin)
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span class="card-title">Nueva factura</span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="alternarAlta()">
                    Mostrar / ocultar
                </button>
            </div>

            <form method="POST" action="{{ route('admin.facturacion.guardar') }}"
                  class="fc-form" id="form-alta"
                  style="display:{{ $errors->any() ? 'block' : 'none' }};">
                @csrf

                <div class="fc-grid">
                    <div class="campo">
                        <label for="company_id">Empresa *</label>
                        <select id="company_id" name="company_id" required>
                            <option value="">— Elegir —</option>
                            @foreach ($empresas as $opcion)
                                <option value="{{ $opcion->id }}"
                                    @selected((int) old('company_id', $empresa?->id) === $opcion->id)>
                                    {{ $opcion->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="campo">
                        <label for="billing_month">Mes *</label>
                        <select id="billing_month" name="billing_month" required>
                            @foreach ($meses as $numero => $nombre)
                                <option value="{{ $numero }}"
                                    @selected((int) old('billing_month', now()->month) === $numero)>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="campo">
                        <label for="billing_year">Año *</label>
                        <input type="number" id="billing_year" name="billing_year" min="2020" max="2100"
                               required value="{{ old('billing_year', now()->year) }}">
                    </div>

                    <div class="campo">
                        <label for="currency_code">Moneda *</label>
                        <select id="currency_code" name="currency_code" required>
                            @foreach (['USD', 'ARS', 'EUR'] as $moneda)
                                <option value="{{ $moneda }}" @selected(old('currency_code', 'USD') === $moneda)>
                                    {{ $moneda }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="campo">
                        <label for="subtotal_amount">Subtotal</label>
                        <input type="number" id="subtotal_amount" name="subtotal_amount"
                               step="0.01" min="0" value="{{ old('subtotal_amount') }}">
                    </div>

                    <div class="campo">
                        <label for="tax_amount">Impuestos</label>
                        <input type="number" id="tax_amount" name="tax_amount"
                               step="0.01" min="0" value="{{ old('tax_amount') }}">
                    </div>

                    <div class="campo">
                        <label for="total_amount">Total *</label>
                        <input type="number" id="total_amount" name="total_amount"
                               step="0.01" min="0" required value="{{ old('total_amount') }}">
                    </div>

                    <div class="campo">
                        <label for="invoice_number">N° de factura</label>
                        <input type="text" id="invoice_number" name="invoice_number"
                               maxlength="20" value="{{ old('invoice_number') }}">
                    </div>

                    <div class="campo">
                        <label for="issue_date">Emisión *</label>
                        <input type="date" id="issue_date" name="issue_date" required
                               value="{{ old('issue_date', now()->toDateString()) }}">
                    </div>

                    <div class="campo">
                        <label for="due_date">Vencimiento</label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}">
                    </div>

                    <div class="campo">
                        <label for="afip_cae">CAE (AFIP)</label>
                        <input type="text" id="afip_cae" name="afip_cae" maxlength="20"
                               value="{{ old('afip_cae') }}">
                    </div>

                    <div class="campo">
                        <label for="afip_cae_expiry">Vto. del CAE</label>
                        <input type="date" id="afip_cae_expiry" name="afip_cae_expiry"
                               value="{{ old('afip_cae_expiry') }}">
                    </div>

                    <div class="campo ancho">
                        <label for="concept">Concepto</label>
                        <input type="text" id="concept" name="concept" maxlength="1000"
                               placeholder="Ej: Abono mensual del canal de denuncias"
                               value="{{ old('concept') }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Crear factura</button>
            </form>
        </div>
    @endif

    {{-- ── Listado ── --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                @if ($facturas->total())
                    {{ $facturas->total() }} {{ \Illuminate\Support\Str::plural('factura', $facturas->total()) }}
                @else
                    Sin facturas
                @endif
            </span>

            <form method="GET" action="{{ route('admin.facturacion.index') }}"
                  style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                @if ($empresa)
                    <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                @endif

                <select name="status" onchange="this.form.submit()"
                        style="padding:6px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $clave => [$clase, $etiqueta])
                        <option value="{{ $clave }}" @selected(($filtros['status'] ?? null) === $clave)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>

                @if ($anios->isNotEmpty())
                    <select name="anio" onchange="this.form.submit()"
                            style="padding:6px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
                        <option value="">Todos los años</option>
                        @foreach ($anios as $anio)
                            <option value="{{ $anio }}" @selected((int) ($filtros['anio'] ?? 0) === $anio)>
                                {{ $anio }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </form>
        </div>

        <div class="table-wrapper">
            @if ($facturas->isEmpty())
                <div class="empty-state">
                    <div class="icon">🧾</div>
                    <p>No hay facturas con los filtros aplicados.</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Período</th>
                            @unless ($empresa)
                                <th>Empresa</th>
                            @endunless
                            <th>N°</th>
                            <th>Concepto</th>
                            <th>Total</th>
                            <th>Emisión</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                            @if ($esSuperadmin)
                                <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($facturas as $factura)
                            @php
                                [$claseEstado, $etiquetaEstado] = $estados[$factura->payment_status]
                                    ?? ['badge-inactivo', $factura->payment_status];
                            @endphp

                            <tr>
                                <td><strong>{{ $meses[$factura->billing_month] }} {{ $factura->billing_year }}</strong></td>
                                @unless ($empresa)
                                    <td>{{ $factura->empresa->name }}</td>
                                @endunless
                                <td>{{ $factura->invoice_number ?? '—' }}</td>
                                <td style="font-size:12px;color:var(--text-light);max-width:240px;">
                                    {{ \Illuminate\Support\Str::limit($factura->concept, 60) ?: '—' }}
                                </td>
                                <td><strong>{{ $plata($factura->total_amount, $factura->currency_code) }}</strong></td>
                                <td>{{ $factura->issue_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    {{ $factura->due_date?->format('d/m/Y') ?? '—' }}
                                    @if ($factura->payment_status === 'paid' && $factura->payment_date)
                                        <div style="font-size:11px;color:#28a745;">
                                            Pagada {{ $factura->payment_date->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td><span class="{{ $claseEstado }}">{{ $etiquetaEstado }}</span></td>

                                @if ($esSuperadmin)
                                    <td>
                                        <div style="display:flex;gap:6px;">
                                            @if (! in_array($factura->payment_status, ['paid', 'cancelled'], true))
                                                <button type="button" class="btn btn-primary btn-sm"
                                                        onclick="alternarFila('pago-{{ $factura->id }}')">
                                                    Registrar pago
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                    onclick="alternarFila('editar-{{ $factura->id }}')">
                                                Editar
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>

                            @if ($esSuperadmin)
                                {{-- Registrar pago --}}
                                <tr class="fila-accion" id="pago-{{ $factura->id }}">
                                    <td colspan="9">
                                        <form method="POST" action="{{ route('admin.facturacion.pagar', $factura) }}"
                                              style="display:flex;gap:10px;align-items:flex-end;padding:8px 0;flex-wrap:wrap;">
                                            @csrf
                                            <div class="campo">
                                                <label for="payment_date_{{ $factura->id }}">Fecha del pago *</label>
                                                <input type="date" id="payment_date_{{ $factura->id }}"
                                                       name="payment_date" required
                                                       max="{{ now()->toDateString() }}"
                                                       value="{{ now()->toDateString() }}">
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">Confirmar pago</button>
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                    onclick="alternarFila('pago-{{ $factura->id }}')">Cancelar</button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Editar --}}
                                <tr class="fila-accion" id="editar-{{ $factura->id }}">
                                    <td colspan="9">
                                        <form method="POST" action="{{ route('admin.facturacion.actualizar', $factura) }}"
                                              style="padding:8px 0;">
                                            @csrf
                                            @method('PUT')

                                            <div class="fc-grid">
                                                <div class="campo">
                                                    <label>Subtotal</label>
                                                    <input type="number" name="subtotal_amount" step="0.01" min="0"
                                                           value="{{ $factura->subtotal_amount }}">
                                                </div>
                                                <div class="campo">
                                                    <label>Impuestos</label>
                                                    <input type="number" name="tax_amount" step="0.01" min="0"
                                                           value="{{ $factura->tax_amount }}">
                                                </div>
                                                <div class="campo">
                                                    <label>Total *</label>
                                                    <input type="number" name="total_amount" step="0.01" min="0"
                                                           required value="{{ $factura->total_amount }}">
                                                </div>
                                                <div class="campo">
                                                    <label>N° de factura</label>
                                                    <input type="text" name="invoice_number" maxlength="20"
                                                           value="{{ $factura->invoice_number }}">
                                                </div>
                                                <div class="campo">
                                                    <label>Vencimiento</label>
                                                    <input type="date" name="due_date"
                                                           value="{{ $factura->due_date?->toDateString() }}">
                                                </div>
                                                <div class="campo">
                                                    <label>CAE</label>
                                                    <input type="text" name="afip_cae" maxlength="20"
                                                           value="{{ $factura->afip_cae }}">
                                                </div>
                                                <div class="campo">
                                                    <label>Vto. del CAE</label>
                                                    <input type="date" name="afip_cae_expiry"
                                                           value="{{ $factura->afip_cae_expiry?->toDateString() }}">
                                                </div>
                                                <div class="campo ancho">
                                                    <label>Concepto</label>
                                                    <input type="text" name="concept" maxlength="1000"
                                                           value="{{ $factura->concept }}">
                                                </div>
                                            </div>

                                            <div style="display:flex;gap:8px;">
                                                <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                                                <button type="button" class="btn btn-secondary btn-sm"
                                                        onclick="alternarFila('editar-{{ $factura->id }}')">Cancelar</button>
                                            </div>
                                        </form>

                                        @if ($factura->payment_status !== 'cancelled')
                                            <form method="POST" action="{{ route('admin.facturacion.anular', $factura) }}"
                                                  style="display:flex;gap:10px;align-items:flex-end;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);flex-wrap:wrap;"
                                                  onsubmit="return confirm('¿Anular esta factura? Queda registrada como anulada, no se elimina.');">
                                                @csrf
                                                <div class="campo" style="flex:1;min-width:220px;">
                                                    <label>Motivo de anulación *</label>
                                                    <input type="text" name="motivo" maxlength="500" required
                                                           placeholder="Ej: emitida por error">
                                                </div>
                                                <button type="submit" class="btn btn-secondary btn-sm">Anular factura</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if ($facturas->hasPages())
            <div class="pagination">
                @if ($facturas->currentPage() > 1)
                    <a href="{{ $facturas->previousPageUrl() }}">‹</a>
                @endif

                @foreach (range(
                    max(1, $facturas->currentPage() - 2),
                    min($facturas->lastPage(), $facturas->currentPage() + 2)
                ) as $numero)
                    @if ($numero === $facturas->currentPage())
                        <span class="current">{{ $numero }}</span>
                    @else
                        <a href="{{ $facturas->url($numero) }}">{{ $numero }}</a>
                    @endif
                @endforeach

                @if ($facturas->currentPage() < $facturas->lastPage())
                    <a href="{{ $facturas->nextPageUrl() }}">›</a>
                @endif
            </div>
        @endif
    </div>

    @unless ($esSuperadmin)
        <div class="card" style="margin-top:16px;">
            <div style="padding:14px 20px;font-size:12px;color:var(--text-light);line-height:1.6;">
                Podés consultar tus facturas. Si encontrás algún
                error, comunicate con tu responsable de cuenta.
            </div>
        </div>
    @endunless

    <script>
        function alternarAlta() {
            const form = document.getElementById('form-alta');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function alternarFila(id) {
            const fila = document.getElementById(id);
            fila.style.display = fila.style.display === 'table-row' ? 'none' : 'table-row';
        }
    </script>

</x-admin.layout>
