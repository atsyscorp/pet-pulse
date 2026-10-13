{{-- GET search box shared by the list pages. --}}
<form method="GET" class="mt-6" role="search">
    <label class="relative block">
        <span class="sr-only">Buscar</span>
        <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-muted" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" name="q" value="{{ $search }}" placeholder="{{ $placeholder }}" class="field !pl-12" autocomplete="off">
    </label>
</form>
