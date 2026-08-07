<x-admin.layout :titulo="$seccion">

    <div class="card">
        <div class="empty-state">
            <div class="icon">🚧</div>
            <p>
                <strong>{{ $seccion }}</strong> todavía no fue portada a Laravel.
            </p>
            <p style="font-size:12px;color:var(--text-light);margin-top:8px;">
                La sección sigue disponible en el sistema anterior mientras dura la migración.
            </p>
        </div>
    </div>

</x-admin.layout>
