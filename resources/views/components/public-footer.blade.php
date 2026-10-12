<footer class="border-t border-white/10 bg-[#0d0928]">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div class="grid gap-10 md:grid-cols-3">
            <div>
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/icon-192.png') }}" alt="" width="40" height="40" class="size-10 rounded-xl">
                    <span class="text-xl font-extrabold text-white">Pet<span class="text-neon-cyan">Pulse</span></span>
                </a>
                <p class="mt-4 max-w-sm text-sm text-slate-400">
                    Plataforma de hospitalización y kárdex de enfermería para clínicas veterinarias. Hecha en Colombia para LATAM.
                </p>
            </div>
            <div class="text-sm">
                <p class="font-bold text-white">Producto</p>
                <ul class="mt-3 space-y-2 text-slate-400">
                    <li><a href="{{ route('home') }}#funciones" class="hover:text-white">Funciones</a></li>
                    <li><a href="{{ route('home') }}#como-funciona" class="hover:text-white">Cómo funciona</a></li>
                    <li><a href="{{ route('home') }}#para-quien" class="hover:text-white">Para quién</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white">Iniciar sesión</a></li>
                </ul>
            </div>
            <div class="text-sm">
                <p class="font-bold text-white">Contacto</p>
                <ul class="mt-3 space-y-2 text-slate-400">
                    <li><a href="mailto:{{ config('petpulse.contact_email') }}" class="hover:text-white">{{ config('petpulse.contact_email') }}</a></li>
                    @if (config('petpulse.whatsapp'))
                        <li><a href="https://wa.me/{{ config('petpulse.whatsapp') }}" class="hover:text-white" rel="noopener" target="_blank">WhatsApp</a></li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="mt-10 flex flex-col gap-2 border-t border-white/10 pt-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} PetPulse · ATSYS. Todos los derechos reservados.</p>
            <p>Los datos de propietarios y pacientes se tratan conforme a la Ley 1581 de 2012 (protección de datos personales, Colombia).</p>
        </div>
    </div>
</footer>
