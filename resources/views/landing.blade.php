<x-layouts.public>
    <x-public-nav />

    {{-- ───────────── Portada ───────────── --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -left-40 top-10 size-[32rem] rounded-full bg-neon-violet/25 blur-3xl"></div>
            <div class="absolute -right-40 top-40 size-[32rem] rounded-full bg-neon-cyan/20 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid opacity-[0.07]"></div>
        </div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 pb-20 pt-16 sm:px-6 lg:grid-cols-2 lg:pb-28 lg:pt-24">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-neon-cyan/40 bg-neon-cyan/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-neon-cyan">
                    <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-neon-cyan opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-neon-cyan"></span></span>
                    Kárdex en tiempo real
                </p>
                <h1 class="mt-5 text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Hospitalización veterinaria con control de <span class="text-glow-cyan text-neon-cyan">cada dosis</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg text-slate-300">
                    PetPulse es la plataforma de hospitalización y enfermería para clínicas veterinarias:
                    programe tratamientos, confirme administraciones desde la tableta y descuente el inventario
                    con precisión de microdosis, todo sincronizado al instante en cada pantalla de la UCI.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="#contacto" class="btn-neon justify-center px-6 py-3.5 text-base">Solicitar demostración</a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl px-6 py-3.5 text-base font-bold text-white ring-1 ring-inset ring-white/20 hover:bg-white/5">Iniciar sesión</a>
                </div>
                <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 text-center">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                        <dt class="text-xs font-semibold uppercase text-slate-400">Precisión</dt>
                        <dd class="text-2xl font-black text-white">0,0001</dd>
                        <dd class="text-xs text-slate-400">mL por dosis</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                        <dt class="text-xs font-semibold uppercase text-slate-400">Sincronía</dt>
                        <dd class="text-2xl font-black text-white">&lt; 1 s</dd>
                        <dd class="text-xs text-slate-400">entre pantallas</dd>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                        <dt class="text-xs font-semibold uppercase text-slate-400">Trazabilidad</dt>
                        <dd class="text-2xl font-black text-white">100 %</dd>
                        <dd class="text-xs text-slate-400">de movimientos</dd>
                    </div>
                </dl>
            </div>

            <div class="relative mx-auto w-full max-w-md lg:max-w-lg">
                <div class="absolute inset-0 -z-10 rounded-[2.5rem] bg-gradient-to-br from-neon-cyan/30 to-neon-violet/30 blur-2xl" aria-hidden="true"></div>
                <img
                    src="{{ asset('images/petpulse-logo.webp') }}"
                    alt="Logotipo de PetPulse: huella y trazo de electrocardiograma en neón"
                    width="1024" height="1080"
                    class="w-full drop-shadow-[0_0_40px_rgba(34,211,238,0.35)]"
                    fetchpriority="high"
                >
            </div>
        </div>
    </section>

    {{-- ───────────── Funciones ───────────── --}}
    <section id="funciones" class="scroll-mt-20 border-t border-white/10 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <p class="text-sm font-bold uppercase tracking-wider text-neon-violet">Funciones</p>
            <h2 class="mt-2 max-w-2xl text-3xl font-black text-white sm:text-4xl">Diseñado para el ritmo de una UCI veterinaria</h2>

            <div class="mt-12 grid gap-6 md:grid-cols-2">
                <article class="card-neon">
                    <div class="icon-neon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l3-8 4 16 3-8h4"/></svg>
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-white">Kárdex de enfermería en tiempo real</h3>
                    <p class="mt-2 text-slate-300">Tablero por paciente y hora, ordenado por nivel de triage. Cada dosis administrada aparece al instante en las tabletas de cabecera y en la pantalla de la sala, sin recargar.</p>
                </article>
                <article class="card-neon">
                    <div class="icon-neon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6l1 4H8l1-4Zm-2 4h10v13a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V7Zm0 5h10"/></svg>
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-white">Inventario fraccional exacto</h3>
                    <p class="mt-2 text-slate-300">Viales sellados y volumen abierto se controlan por separado, con cuatro decimales. Al administrar una dosis mayor que el remanente, el sistema abre el siguiente vial automáticamente y lo registra.</p>
                </article>
                <article class="card-neon">
                    <div class="icon-neon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4Zm-3 9 2 2 4-4"/></svg>
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-white">Trazabilidad y control especial</h3>
                    <p class="mt-2 text-slate-300">Libro de movimientos inalterable: apertura, administración y descarte, con responsable y hora. Los medicamentos de control especial exigen observación obligatoria en cada aplicación.</p>
                </article>
                <article class="card-neon">
                    <div class="icon-neon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 21V8l8-5 8 5v13M9 21v-6h6v6M4 12h16"/></svg>
                    </div>
                    <h3 class="mt-5 text-xl font-bold text-white">Varias sedes, una plataforma</h3>
                    <p class="mt-2 text-slate-300">Cada clínica trabaja con sus propios pacientes, inventario y personal, aislados por completo. Crezca de una sala a una red de hospitales sin cambiar de sistema.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- ───────────── Cómo funciona ───────────── --}}
    <section id="como-funciona" class="scroll-mt-20 border-t border-white/10 bg-white/[0.03] py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <p class="text-sm font-bold uppercase tracking-wider text-neon-cyan">Cómo funciona</p>
            <h2 class="mt-2 max-w-2xl text-3xl font-black text-white sm:text-4xl">Del ingreso a la administración en tres pasos</h2>

            <ol class="mt-12 grid gap-6 lg:grid-cols-3">
                <li class="card-neon">
                    <span class="step-neon">1</span>
                    <h3 class="mt-5 text-xl font-bold text-white">Ingreso del paciente</h3>
                    <p class="mt-2 text-slate-300">Registre la hospitalización con peso actual, nivel de triage (crítico, intermedio u observación) y cama asignada.</p>
                </li>
                <li class="card-neon">
                    <span class="step-neon">2</span>
                    <h3 class="mt-5 text-xl font-bold text-white">Prescripción</h3>
                    <p class="mt-2 text-slate-300">El veterinario programa cada medicamento con dosis, vía y horario. El kárdex se llena solo para todo el día.</p>
                </li>
                <li class="card-neon">
                    <span class="step-neon">3</span>
                    <h3 class="mt-5 text-xl font-bold text-white">Administración confirmada</h3>
                    <p class="mt-2 text-slate-300">La auxiliar toca la dosis en la tableta, verifica los "5 correctos" y confirma. El inventario se descuenta y todas las pantallas se actualizan.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- ───────────── Para quién ───────────── --}}
    <section id="para-quien" class="scroll-mt-20 border-t border-white/10 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <p class="text-sm font-bold uppercase tracking-wider text-neon-violet">Para quién</p>
            <h2 class="mt-2 max-w-2xl text-3xl font-black text-white sm:text-4xl">Para equipos que hospitalizan</h2>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <div class="rounded-3xl border border-white/10 p-6">
                    <h3 class="text-lg font-bold text-white">Clínicas veterinarias</h3>
                    <p class="mt-2 text-slate-300">Con una o dos camas de hospitalización que necesitan dejar atrás el kárdex en papel y el inventario en hojas de cálculo.</p>
                </div>
                <div class="rounded-3xl border border-white/10 p-6">
                    <h3 class="text-lg font-bold text-white">Hospitales veterinarios</h3>
                    <p class="mt-2 text-slate-300">Con varios turnos de enfermería que requieren que todos vean el mismo estado, al mismo tiempo, sin dobles administraciones.</p>
                </div>
                <div class="rounded-3xl border border-white/10 p-6">
                    <h3 class="text-lg font-bold text-white">Unidades de cuidado intensivo</h3>
                    <p class="mt-2 text-slate-300">Con microdosis, infusiones y medicamentos de control especial donde cada 0,01 mL y cada firma cuentan.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────── Contacto ───────────── --}}
    <section id="contacto" class="scroll-mt-20 border-t border-white/10 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-neon-violet/20 via-night to-neon-cyan/20 p-8 sm:p-12">
                <div class="relative grid items-center gap-8 lg:grid-cols-2">
                    <div>
                        <h2 class="text-3xl font-black text-white sm:text-4xl">Véalo funcionando con sus propios casos</h2>
                        <p class="mt-4 text-lg text-slate-300">Agende una demostración de 30 minutos. Le mostramos el kárdex con pacientes reales de su clínica y resolvemos sus preguntas sobre inventario, control especial y puesta en marcha.</p>
                    </div>
                    <div class="flex flex-col gap-3">
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hola, quiero una demostración de PetPulse para mi clínica.') }}" target="_blank" rel="noopener" class="btn-neon justify-center px-6 py-4 text-base">
                                Escribir por WhatsApp
                            </a>
                        @endif
                        <a href="mailto:{{ $contactEmail }}?subject={{ rawurlencode('Demostración de PetPulse') }}" class="{{ $whatsapp ? 'inline-flex items-center justify-center rounded-xl px-6 py-4 text-base font-bold text-white ring-1 ring-inset ring-white/20 hover:bg-white/5' : 'btn-neon justify-center px-6 py-4 text-base' }}">
                            Escribir a {{ $contactEmail }}
                        </a>
                        <p class="text-center text-xs text-slate-400">Respondemos el mismo día hábil.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <x-public-footer />
</x-layouts.public>
