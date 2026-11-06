<div class="px-6 py-16 text-center">
    <p class="text-xl font-bold text-ink">{{ $search !== '' ? 'Sin resultados para "'.$search.'"' : $empty }}</p>
    @if ($search !== '')
        <p class="mt-2 text-muted">Pruebe con otro nombre o correo.</p>
    @endif
</div>
