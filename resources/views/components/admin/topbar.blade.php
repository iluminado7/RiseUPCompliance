@props(['titulo' => 'Panel'])

<div class="topbar">
    <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
    <span class="topbar-title">{{ $titulo }}</span>

    <div class="topbar-right">
        <span class="role-badge">{{ auth()->user()->nombreRol()?->etiqueta() }}</span>
        <span class="topbar-fecha">{{ now()->format('d/m/Y') }}</span>

        <div class="header-notif-wrap" id="header-notif-wrap">
            <button class="header-notif-btn" id="btn-campana"
                    onclick="abrirCampana(event)"
                    title="Notificaciones" aria-label="Notificaciones">
                🔔
                <span class="header-notif-badge" id="notif-badge" style="display:none;"></span>
            </button>

            <div class="header-notif-panel" id="header-notif-panel">
                <div class="hnp-header">
                    <span class="hnp-title">
                        Notificaciones
                        <span class="hnp-count" id="hnp-count" style="display:none;"></span>
                    </span>
                    <button class="hnp-mark-all" id="btn-mark-all"
                            onclick="marcarTodasLeidas()" style="display:none;">
                        Marcar leídas
                    </button>
                </div>
                <div class="hnp-list" id="hnp-list">
                    <div class="hnp-empty">Cargando...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.topbar { justify-content: flex-start; gap: 12px; }
.topbar-right { margin-left: auto; display: flex; align-items: center; gap: 12px; }
.topbar-fecha { font-size: 13px; color: var(--text-light); }

.header-notif-wrap { position: relative; }
.header-notif-btn {
    background: none; border: none; font-size: 18px; cursor: pointer;
    position: relative; padding: 6px 8px; border-radius: 8px;
    transition: background .15s; line-height: 1; min-width: auto; color: var(--text);
}
.header-notif-btn:hover { background: #f0f0f0; transform: none; }
.header-notif-badge {
    position: absolute; top: 2px; right: 2px;
    background: #b00020; color: #fff; font-size: 9px; font-weight: 700;
    min-width: 16px; height: 16px; border-radius: 999px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 4px; pointer-events: none;
}

.header-notif-panel {
    display: none; position: absolute; top: calc(100% + 8px); right: 0;
    width: 320px; background: #fff; border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,.16); border: 1px solid var(--border);
    z-index: 500; overflow: hidden;
}
.header-notif-panel.open { display: block; }

.hnp-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 16px; border-bottom: 1px solid #f0f0f0; background: #fafafa;
}
.hnp-title { font-size: 13px; font-weight: 700; color: var(--dark); }
.hnp-count { font-size: 11px; font-weight: 700; color: #b8962e; margin-left: 6px; }
.hnp-mark-all {
    font-size: 11px; color: #777; background: none; border: none;
    cursor: pointer; font-weight: 600; padding: 0; min-width: auto;
}
.hnp-mark-all:hover { color: #333; transform: none; background: none; }

.hnp-list { max-height: 340px; overflow-y: auto; }
.hnp-empty { padding: 28px 16px; text-align: center; font-size: 13px; color: #aaa; }

.hnp-item {
    padding: 10px 36px 10px 14px; border-bottom: 1px solid #f5f5f5;
    cursor: pointer; position: relative; transition: background .15s;
}
.hnp-item:hover { background: #fafafa; }
.hnp-item:last-child { border-bottom: none; }
.hnp-unread { background: rgba(191,174,118,.07); }
.hnp-unread::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; background: var(--gold); border-radius: 0 2px 2px 0;
}
.hnp-text { font-size: 12px; color: #333; font-weight: 600; margin-bottom: 3px; line-height: 1.4; }
.hnp-code { font-size: 11px; color: #b8962e; font-weight: 700; margin-left: 5px; }
.hnp-meta { font-size: 11px; color: #aaa; }
.hnp-dismiss {
    position: absolute; top: 8px; right: 8px;
    background: none; border: none; font-size: 15px; color: #ccc;
    cursor: pointer; padding: 2px 5px; line-height: 1; border-radius: 4px;
    min-width: auto; transition: color .15s, background .15s;
}
.hnp-dismiss:hover { color: #b00020; background: #fde8e8; transform: none; }

@media (max-width: 480px) {
    .header-notif-panel { right: -8px; width: calc(100vw - 24px); }
    .topbar-fecha { display: none; }
}
</style>

<script>
const _NOTIF = {
    contar:  '{{ route('admin.notificaciones.contar') }}',
    listar:  '{{ route('admin.notificaciones.listar') }}',
    leer:    '{{ route('admin.notificaciones.leer') }}',
    leerTodas: '{{ route('admin.notificaciones.leer-todas') }}',
    csrf:    '{{ csrf_token() }}',
};

let _panelCargado = false;

(async function contarBadge() {
    try {
        const r = await fetch(_NOTIF.contar);
        const d = await r.json();
        if (d.total > 0) {
            const badge = document.getElementById('notif-badge');
            badge.textContent = d.total > 99 ? '99+' : d.total;
            badge.style.display = 'flex';
        }
    } catch (e) {}
})();

async function abrirCampana(e) {
    e.stopPropagation();
    const panel = document.getElementById('header-notif-panel');

    if (panel.classList.contains('open')) {
        panel.classList.remove('open');
        return;
    }

    panel.classList.add('open');

    if (_panelCargado) return;

    document.getElementById('hnp-list').innerHTML = '<div class="hnp-empty">Cargando...</div>';

    try {
        const r = await fetch(_NOTIF.listar);
        const d = await r.json();
        render(d.notificaciones);
        _panelCargado = true;
        document.getElementById('notif-badge').style.display = 'none';
    } catch (e) {
        document.getElementById('hnp-list').innerHTML =
            '<div class="hnp-empty">Error al cargar notificaciones.</div>';
    }
}

function render(notifs) {
    const list = document.getElementById('hnp-list');
    const markAll = document.getElementById('btn-mark-all');
    const count = document.getElementById('hnp-count');

    if (!notifs || notifs.length === 0) {
        list.innerHTML = '<div class="hnp-empty">✓ Sin notificaciones pendientes</div>';
        markAll.style.display = 'none';
        count.style.display = 'none';
        return;
    }

    const pendientes = notifs.filter(n => !n.leida).length;
    if (pendientes > 0) {
        count.textContent = pendientes + ' nueva' + (pendientes !== 1 ? 's' : '');
        count.style.display = 'inline';
        markAll.style.display = 'inline';
    }

    // textContent en lugar de interpolar en el HTML: los campos vienen de
    // la base y podrían contener markup.
    list.innerHTML = '';
    notifs.forEach(n => {
        const item = document.createElement('div');
        item.className = 'hnp-item' + (n.leida ? '' : ' hnp-unread');
        item.id = 'notif-' + n.id;
        item.onclick = () => irA(n);

        const texto = document.createElement('div');
        texto.className = 'hnp-text';
        texto.textContent = n.titulo;
        if (n.referencia) {
            const ref = document.createElement('span');
            ref.className = 'hnp-code';
            ref.textContent = n.referencia;
            texto.appendChild(ref);
        }

        const meta = document.createElement('div');
        meta.className = 'hnp-meta';
        meta.textContent = (n.empresa ? n.empresa + ' · ' : '') + n.fecha;

        const cerrar = document.createElement('button');
        cerrar.className = 'hnp-dismiss';
        cerrar.title = 'Descartar';
        cerrar.textContent = '×';
        cerrar.onclick = (ev) => { ev.stopPropagation(); marcarLeida(n.id); };

        item.append(texto, meta, cerrar);
        list.appendChild(item);
    });
}

function irA(n) {
    marcarLeida(n.id, false);
    if (n.url) window.location.href = n.url;
}

function marcarLeida(id, quitar = true) {
    fetch(_NOTIF.leer, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _NOTIF.csrf },
        body: JSON.stringify({ id }),
    }).then(() => {
        if (!quitar) return;
        const el = document.getElementById('notif-' + id);
        if (!el) return;
        el.style.transition = 'opacity .2s';
        el.style.opacity = '0';
        setTimeout(() => {
            el.remove();
            const lista = document.getElementById('hnp-list');
            if (lista && !lista.querySelector('.hnp-item')) {
                lista.innerHTML = '<div class="hnp-empty">✓ Sin notificaciones pendientes</div>';
                document.getElementById('btn-mark-all').style.display = 'none';
                document.getElementById('hnp-count').style.display = 'none';
            }
        }, 200);
    });
}

function marcarTodasLeidas() {
    fetch(_NOTIF.leerTodas, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _NOTIF.csrf },
    }).then(() => {
        document.getElementById('hnp-list').innerHTML =
            '<div class="hnp-empty">✓ Sin notificaciones pendientes</div>';
        document.getElementById('notif-badge').style.display = 'none';
        document.getElementById('hnp-count').style.display = 'none';
        document.getElementById('btn-mark-all').style.display = 'none';
    });
}

document.addEventListener('click', function (e) {
    const panel = document.getElementById('header-notif-panel');
    const wrap = document.getElementById('header-notif-wrap');
    if (panel && wrap && !wrap.contains(e.target)) {
        panel.classList.remove('open');
    }
});
</script>
