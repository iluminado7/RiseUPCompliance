<x-publico.onboarding.layout titulo="Formulario enviado">

    <div class="ob-card" style="text-align:center;padding:48px 32px;">
        <div style="font-size:44px;margin-bottom:14px;">✓</div>
        <h2>Recibimos tus datos</h2>
        <p class="ayuda">
            @if ($empresa)
                El alta de <strong>{{ $empresa }}</strong> quedó pendiente de revisión.
            @else
                El alta quedó pendiente de revisión.
            @endif
            Vamos a confirmarla y te avisamos cuando el canal esté disponible.
        </p>
        <p style="font-size:12px;color:var(--text-light);margin-top:20px;">
            Ya podés cerrar esta ventana. Este enlace dejó de estar activo.
        </p>
    </div>

</x-publico.onboarding.layout>
