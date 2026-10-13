<footer class="border-t border-line bg-sunken">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div class="grid gap-10 md:grid-cols-3">
            <div>
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/icon-192.png') }}" alt="" width="40" height="40" class="size-10 rounded-xl">
                    <span class="text-xl font-extrabold text-ink">Pet<span class="text-trace">Pulse</span></span>
                </a>
                <p class="mt-4 max-w-sm text-sm text-muted">
                    Plataforma de hospitalización y kárdex de enfermería para clínicas veterinarias. Hecha en Colombia para LATAM.
                </p>
            </div>
            <div class="text-sm">
                <p class="font-bold text-ink">Producto</p>
                <ul class="mt-3 text-muted">
                    <li><a href="{{ route('home') }}#funciones" class="flex min-h-12 items-center hover:text-ink">Funciones</a></li>
                    <li><a href="{{ route('home') }}#como-funciona" class="flex min-h-12 items-center hover:text-ink">Cómo funciona</a></li>
                    <li><a href="{{ route('home') }}#para-quien" class="flex min-h-12 items-center hover:text-ink">Para quién</a></li>
                    <li><a href="{{ route('access.create') }}" class="flex min-h-12 items-center hover:text-ink">Solicitar acceso</a></li>
                    <li><a href="{{ route('login') }}" class="flex min-h-12 items-center hover:text-ink">Iniciar sesión</a></li>
                </ul>
            </div>
            <div class="text-sm">
                <p class="font-bold text-ink">Contacto</p>
                <ul class="mt-3 text-muted">
                    <li><a href="mailto:{{ config('petpulse.contact_email') }}" class="flex min-h-12 items-center hover:text-ink">{{ config('petpulse.contact_email') }}</a></li>
                    @if (config('petpulse.whatsapp'))
                        <li><a href="https://wa.me/{{ config('petpulse.whatsapp') }}" class="flex min-h-12 items-center hover:text-ink" rel="noopener" target="_blank">WhatsApp</a></li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="mt-10 flex flex-col gap-2 border-t border-line pt-6 text-xs text-muted sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} PetPulse · ATSYS. Todos los derechos reservados.</p>
            <p>Los datos de propietarios y pacientes se tratan conforme a la Ley 1581 de 2012 (protección de datos personales, Colombia).</p>
        </div>
    </div>
</footer>
