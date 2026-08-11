<x-admin.layout :titulo="'Preguntas — ' . $categoria->name_es">

    <style>
        .pq-item {
            background:var(--white); border:1px solid var(--border); border-radius:10px;
            padding:16px 18px; margin-bottom:10px;
        }
        .pq-cabecera { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
        .pq-orden {
            background:var(--gold); color:var(--dark); font-weight:700; font-size:12px;
            width:26px; height:26px; border-radius:50%; display:flex;
            align-items:center; justify-content:center; flex-shrink:0;
        }
        .pq-texto { flex:1; font-size:14px; line-height:1.5; }
        .pq-meta { font-size:11px; color:var(--text-light); margin-top:6px; }
        .pq-form { display:none; margin-top:12px; }
        .pq-form textarea {
            width:100%; padding:10px 13px; border:1px solid var(--border);
            border-radius:8px; font-size:14px; font-family:inherit; resize:vertical;
        }
    </style>

    <div style="margin-bottom:16px;">
        <a href="{{ route('admin.catalogo.index', ['tab' => 'categorias']) }}"
           style="font-size:13px;color:var(--text-light);">← Volver al catálogo</a>
    </div>

    @if ($empresasAfectadas > 0)
        <div class="alert" style="background:#fff3cd;border:1px solid #ffeeba;color:#856404;margin-bottom:20px;">
            <strong>{{ $empresasAfectadas }}</strong>
            {{ \Illuminate\Support\Str::plural('empresa', $empresasAfectadas) }}
            {{ $empresasAfectadas === 1 ? 'tiene' : 'tienen' }} este cuestionario importado.
            Cualquier cambio acá genera una versión nueva en cada una. Las preguntas que
            hayan agregado por su cuenta se conservan.
        </div>
    @endif

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">Nueva pregunta</span>
        </div>

        <form method="POST" action="{{ route('admin.catalogo.preguntas.agregar', $categoria) }}"
              style="padding:16px 20px;">
            @csrf

            <textarea name="question_text_es" rows="2" maxlength="2000" required
                      placeholder="¿Qué se le pregunta al denunciante?"
                      style="width:100%;padding:10px 13px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;margin-bottom:12px;"></textarea>

            <button type="submit" class="btn btn-primary">Agregar pregunta</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                Cuestionario base — {{ $preguntas->count() }}
                {{ \Illuminate\Support\Str::plural('pregunta', $preguntas->count()) }}
            </span>
        </div>

        <div style="padding:16px 20px;">
            @if ($preguntas->isEmpty())
                <div class="empty-state">
                    <div class="icon">❓</div>
                    <p>Esta categoría todavía no tiene preguntas.</p>
                    <p style="font-size:12px;color:var(--text-light);margin-top:8px;">
                        Sin preguntas, el formulario público solo pide los datos generales.
                    </p>
                </div>
            @else
                @foreach ($preguntas as $pregunta)
                    <div class="pq-item">
                        <div class="pq-cabecera">
                            <div class="pq-orden">{{ $pregunta->question_order }}</div>

                            <div class="pq-texto">
                                {{ $pregunta->question_text_es }}
                                <div class="pq-meta">
                                    Versión {{ $pregunta->version }} ·
                                    vigente desde {{ $pregunta->valid_from?->format('d/m/Y') ?? '—' }}
                                </div>
                            </div>

                            <div style="display:flex;gap:6px;flex-shrink:0;">
                                <button type="button" class="btn btn-secondary btn-sm"
                                        onclick="alternarPregunta({{ $pregunta->id }})">Editar</button>

                                <form method="POST"
                                      action="{{ route('admin.catalogo.preguntas.desactivar', [$categoria, $pregunta]) }}"
                                      onsubmit="return confirm('¿Retirar esta pregunta del catálogo? Las empresas vinculadas van a recibir una versión nueva sin ella. Las denuncias ya respondidas conservan la pregunta.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary btn-sm">Quitar</button>
                                </form>
                            </div>
                        </div>

                        <form method="POST"
                              action="{{ route('admin.catalogo.preguntas.editar', [$categoria, $pregunta]) }}"
                              class="pq-form" id="form-pq-{{ $pregunta->id }}">
                            @csrf
                            @method('PUT')

                            <textarea name="question_text_es" rows="2" maxlength="2000"
                                      required>{{ $pregunta->question_text_es }}</textarea>

                            <div style="display:flex;gap:8px;margin-top:10px;">
                                <button type="submit" class="btn btn-primary btn-sm">Guardar cambios</button>
                                <button type="button" class="btn btn-secondary btn-sm"
                                        onclick="alternarPregunta({{ $pregunta->id }})">Cancelar</button>
                            </div>

                            <p style="font-size:11px;color:var(--text-light);margin-top:8px;">
                                El texto no se reescribe: se crea una versión nueva y la anterior
                                queda desactivada. Las denuncias ya respondidas conservan el
                                enunciado con el que se las formuló.
                            </p>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script>
        function alternarPregunta(id) {
            const form = document.getElementById('form-pq-' + id);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }
    </script>

</x-admin.layout>
