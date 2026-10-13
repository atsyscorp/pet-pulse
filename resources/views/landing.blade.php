@php
    // One heartbeat, repeated: P wave, QRS complex, T wave. 156 units wide.
    $beat = 'h26 l6 -8 l8 8 h14 l4 6 l6 -46 l8 58 l5 -18 h16 l8 -10 l10 10 h45';
    $trace = 'M0 80 '.str_repeat($beat.' ', 10);
@endphp
<x-layouts.public>
    <x-public-nav />

    {{-- ───────────── Portada ───────────── --}}
    <section class="relative overflow-hidden">
        {{-- Hero backdrop: an old-monitor phosphor trace sweeping behind the content. Decorative. --}}
        <svg viewBox="0 0 1560 160" preserveAspectRatio="xMidYMid slice" class="hero-trace pointer-events-none absolute inset-x-0 bottom-2 h-40 w-full lg:bottom-3 lg:h-52" aria-hidden="true" focusable="false">
            <path d="{{ $trace }}" class="trace-base" fill="none" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" pathLength="1000"/>
            <path d="{{ $trace }}" class="trace-live" fill="none" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" pathLength="1000"/>
        </svg>

        <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 pb-44 pt-14 sm:px-6 lg:grid-cols-[1.05fr_1fr] lg:gap-16 lg:pb-56 lg:pt-20">
            <div>
                <h1 class="text-balance text-4xl font-extrabold leading-[1.1] tracking-tight text-ink sm:text-5xl lg:text-6xl">
                    Hospitalización veterinaria con control de cada dosis
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-muted">
                    PetPulse es la plataforma de hospitalización y enfermería para clínicas veterinarias:
                    programe tratamientos, confirme administraciones desde la tableta y descuente el inventario
                    con precisión de microdosis, todo sincronizado al instante en cada pantalla de la UCI.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('access.create') }}" class="btn-primary px-7">Solicitar acceso</a>
                    <a href="{{ route('login') }}" class="btn-secondary px-7">Iniciar sesión</a>
                </div>
                <p class="mt-6 max-w-md text-sm text-muted">
                    ¿Prefiere verlo antes? <a href="#contacto" class="link-quiet">Agende una demostración</a>.
                </p>
            </div>

            {{-- Monitor: the signature. Demo data is synthetic and labelled as such. --}}
            <figure class="monitor overflow-hidden" aria-labelledby="monitor-leyenda">
                <div class="flex items-center justify-between border-b border-screen-line px-5 py-3 text-sm">
                    <span class="font-bold">UCI · Sala 1</span>
                    <span class="inline-flex items-center gap-2 text-screen-muted">
                        <span class="size-2 rounded-full bg-screen-ok" aria-hidden="true"></span>
                        Sincronizado
                    </span>
                </div>

                <div class="px-5 pb-5 pt-4">
                    <div class="flex items-baseline justify-between gap-4">
                        <p class="text-lg font-bold">Luna <span class="font-normal text-screen-muted">· Canino · <span class="pp-num">14,2</span> kg</span></p>
                        <span class="rounded-full bg-screen-late-soft px-3 py-1 text-xs font-bold text-screen-late">Crítico</span>
                    </div>

                    <ul class="mt-4 divide-y divide-screen-line text-sm" aria-label="Próximas dosis de ejemplo">
                        <li class="flex items-center gap-3 py-3">
                            <span class="grid size-8 place-items-center rounded-lg bg-screen-ok-soft text-screen-ok" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg>
                            </span>
                            <span class="pp-num w-12 shrink-0 font-bold">08:00</span>
                            <span class="flex-1">Ceftriaxona<span class="block text-xs text-screen-ok sm:hidden">Administrada</span></span>
                            <span class="pp-num whitespace-nowrap font-bold">0,4375 mL</span>
                            <span class="hidden w-24 text-right text-screen-ok sm:block">Administrada</span>
                        </li>
                        <li class="flex items-center gap-3 py-3">
                            <span class="grid size-8 place-items-center rounded-lg bg-screen-soon-soft text-screen-soon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 1.5"/></svg>
                            </span>
                            <span class="pp-num w-12 shrink-0 font-bold">10:00</span>
                            <span class="flex-1">Buprenorfina <span class="hidden text-screen-muted sm:inline">· control especial</span><span class="block text-xs text-screen-soon sm:hidden">Por vencer · control especial</span></span>
                            <span class="pp-num whitespace-nowrap font-bold">0,0125 mL</span>
                            <span class="hidden w-24 text-right text-screen-soon sm:block">Por vencer</span>
                        </li>
                        <li class="flex items-center gap-3 py-3">
                            <span class="grid size-8 place-items-center rounded-lg bg-screen-idle text-screen-muted" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 12h12"/></svg>
                            </span>
                            <span class="pp-num w-12 shrink-0 font-bold">12:00</span>
                            <span class="flex-1">Furosemida<span class="block text-xs text-screen-muted sm:hidden">Pendiente</span></span>
                            <span class="pp-num whitespace-nowrap font-bold">0,8000 mL</span>
                            <span class="hidden w-24 text-right text-screen-muted sm:block">Pendiente</span>
                        </li>
                    </ul>
                </div>
                <figcaption id="monitor-leyenda" class="border-t border-screen-line bg-screen px-5 py-3 text-xs text-screen-muted">
                    Datos ficticios de demostración.
                </figcaption>
            </figure>
        </div>
    </section>

    {{-- ───────────── Funciones ───────────── --}}
    <section id="funciones" class="scroll-mt-20 border-t border-line py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <h2 class="max-w-2xl text-balance text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">Diseñado para el ritmo de una UCI veterinaria</h2>
            <p class="mt-3 text-sm text-muted">Los ejemplos de esta sección usan datos ficticios.</p>

            <div class="mt-12 divide-y divide-line border-y border-line">
                {{-- 1. Kárdex --}}
                <article class="grid gap-8 py-12 lg:grid-cols-12 lg:gap-12">
                    <div class="min-w-0 lg:col-span-5">
                        <h3 class="text-2xl font-bold text-ink">Kárdex de enfermería en tiempo real</h3>
                        <p class="mt-3 text-lg leading-relaxed text-muted">Tablero por paciente y hora, ordenado por nivel de triage. Cada dosis administrada aparece al instante en las tabletas de cabecera y en la pantalla de la sala, sin recargar.</p>
                    </div>
                    <div class="min-w-0 lg:col-span-7">
                        <div class="panel p-5" role="img" aria-label="Ejemplo de tablero: tres pacientes por seis horas, con dosis administradas, vencidas, por vencer y pendientes.">
                            <div class="grid grid-cols-[3.25rem_repeat(6,minmax(0,1fr))] items-center gap-1.5 text-xs sm:grid-cols-[6rem_repeat(6,minmax(0,1fr))] sm:gap-2 sm:text-xs font-bold text-muted" aria-hidden="true">
                                <span></span>
                                @foreach (['06:00', '08:00', '10:00', '12:00', '14:00', '16:00'] as $hour)
                                    <span class="pp-num text-center">{{ $hour }}</span>
                                @endforeach

                                @php
                                    $rows = [
                                        ['Luna', ['ok', 'ok', 'soon', 'pending', 'pending', 'pending']],
                                        ['Max', ['ok', 'late', 'ok', 'pending', 'pending', 'pending']],
                                        ['Nala', ['ok', 'ok', 'ok', 'soon', 'pending', 'pending']],
                                    ];
                                @endphp
                                @foreach ($rows as [$name, $states])
                                    <span class="text-sm text-ink">{{ $name }}</span>
                                    @foreach ($states as $state)
                                        <span class="cell cell-{{ $state }}">
                                            @switch($state)
                                                @case('ok')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg>
                                                    @break
                                                @case('late')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 6v7M12 18v.01"/></svg>
                                                    @break
                                                @case('soon')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 1.5"/></svg>
                                                    @break
                                                @default
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M7 12h10"/></svg>
                                            @endswitch
                                        </span>
                                    @endforeach
                                @endforeach
                            </div>
                            <ul class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted" aria-hidden="true">
                                <li class="flex items-center gap-2"><span class="cell cell-ok !size-6 !h-6 !rounded-md"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg></span>Administrada</li>
                                <li class="flex items-center gap-2"><span class="cell cell-soon !size-6 !h-6 !rounded-md"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 1.5"/></svg></span>Por vencer</li>
                                <li class="flex items-center gap-2"><span class="cell cell-late !size-6 !h-6 !rounded-md"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 6v7M12 18v.01"/></svg></span>Vencida</li>
                                <li class="flex items-center gap-2"><span class="cell cell-pending !size-6 !h-6 !rounded-md"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M7 12h10"/></svg></span>Pendiente</li>
                            </ul>
                        </div>
                    </div>
                </article>

                {{-- 2. Inventario --}}
                <article class="grid gap-8 py-12 lg:grid-cols-12 lg:gap-12">
                    <div class="min-w-0 lg:col-span-5">
                        <h3 class="text-2xl font-bold text-ink">Inventario fraccional exacto</h3>
                        <p class="mt-3 text-lg leading-relaxed text-muted">Viales sellados y volumen abierto se controlan por separado, con cuatro decimales. Al administrar una dosis mayor que el remanente, el sistema abre el siguiente vial automáticamente y lo registra.</p>
                    </div>
                    <div class="min-w-0 lg:col-span-7">
                        <div class="panel p-5">
                            <dl class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-semibold text-muted">Viales sellados</dt>
                                    <dd class="pp-num mt-1 text-3xl font-extrabold text-ink">3 <span class="text-base font-semibold text-muted">× 10 mL</span></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-semibold text-muted">Volumen abierto</dt>
                                    <dd class="pp-num mt-1 text-3xl font-extrabold text-ink">2,4375 <span class="text-base font-semibold text-muted">mL</span></dd>
                                </div>
                            </dl>
                            <div class="mt-5" role="img" aria-label="Vial abierto: 2,4375 mL restantes de 10 mL">
                                <div class="h-3 overflow-hidden rounded-full bg-sunken">
                                    <div class="h-full rounded-full bg-accent" style="width: 24.375%"></div>
                                </div>
                                <div class="pp-num mt-2 flex justify-between text-xs text-muted" aria-hidden="true"><span>0 mL</span><span>10 mL</span></div>
                            </div>
                            <p class="mt-4 rounded-xl bg-sunken px-4 py-3 text-sm text-ink">
                                La próxima dosis de <span class="pp-num font-bold">3,0000 mL</span> abre un vial nuevo y queda anotada con fecha y hora.
                            </p>
                        </div>
                    </div>
                </article>

                {{-- 3. Trazabilidad --}}
                <article class="grid gap-8 py-12 lg:grid-cols-12 lg:gap-12">
                    <div class="min-w-0 lg:col-span-5">
                        <h3 class="text-2xl font-bold text-ink">Trazabilidad y control especial</h3>
                        <p class="mt-3 text-lg leading-relaxed text-muted">Libro de movimientos inalterable: apertura, administración y descarte, con responsable y hora. Los medicamentos de control especial exigen observación obligatoria en cada aplicación.</p>
                    </div>
                    <div class="min-w-0 lg:col-span-7">
                        <div class="panel overflow-x-auto">
                            <table class="w-full min-w-[30rem] text-left text-sm">
                                <caption class="sr-only">Ejemplo de libro de movimientos de un medicamento</caption>
                                <thead class="border-b border-line text-xs font-bold uppercase tracking-wider text-muted">
                                    <tr>
                                        <th scope="col" class="px-5 py-3">Hora</th>
                                        <th scope="col" class="px-3 py-3">Movimiento</th>
                                        <th scope="col" class="px-3 py-3 text-right">Cambio (mL)</th>
                                        <th scope="col" class="px-3 py-3 text-right">Saldo (mL)</th>
                                        <th scope="col" class="px-5 py-3">Responsable</th>
                                    </tr>
                                </thead>
                                <tbody class="pp-num divide-y divide-line text-ink">
                                    <tr><td class="px-5 py-3.5">07:58</td><td class="px-3 py-3.5">Apertura de vial</td><td class="px-3 py-3.5 text-right">+10,0000</td><td class="px-3 py-3.5 text-right font-bold">10,0000</td><td class="px-5 py-3.5">M. Rojas</td></tr>
                                    <tr><td class="px-5 py-3.5">08:02</td><td class="px-3 py-3.5">Administración · Luna</td><td class="px-3 py-3.5 text-right">−0,4375</td><td class="px-3 py-3.5 text-right font-bold">9,5625</td><td class="px-5 py-3.5">M. Rojas</td></tr>
                                    <tr><td class="px-5 py-3.5">10:04</td><td class="px-3 py-3.5">Administración · Max</td><td class="px-3 py-3.5 text-right">−0,0125</td><td class="px-3 py-3.5 text-right font-bold">9,5500</td><td class="px-5 py-3.5">J. Peña</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>

                {{-- 4. Sedes --}}
                <article class="grid gap-8 py-12 lg:grid-cols-12 lg:gap-12">
                    <div class="min-w-0 lg:col-span-5">
                        <h3 class="text-2xl font-bold text-ink">Varias sedes, una plataforma</h3>
                        <p class="mt-3 text-lg leading-relaxed text-muted">Cada clínica trabaja con sus propios pacientes, inventario y personal, aislados por completo. Crezca de una sala a una red de hospitales sin cambiar de sistema.</p>
                    </div>
                    <div class="min-w-0 lg:col-span-7">
                        <div class="panel grid divide-y divide-line sm:grid-cols-3 sm:divide-x sm:divide-y-0" role="img" aria-label="Ejemplo: tres clínicas, cada una con sus propios pacientes e inventario.">
                            @foreach ([['Clínica Norte', '4 en UCI', 'Ceftriaxona', '12,5000 mL'], ['Clínica Centro', '9 en UCI', 'Buprenorfina', '3,2500 mL'], ['Clínica Sur', '2 en UCI', 'Furosemida', '28,0000 mL']] as [$sede, $stay, $drug, $stock])
                                <dl class="p-5" aria-hidden="true">
                                    <dt class="font-bold text-ink">{{ $sede }}</dt>
                                    <dd class="pp-num mt-3 text-2xl font-extrabold text-ink">{{ $stay }}</dd>
                                    <dd class="mt-3 text-sm text-muted">{{ $drug }}</dd>
                                    <dd class="pp-num text-lg font-bold text-ink">{{ $stock }}</dd>
                                </dl>
                            @endforeach
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- ───────────── Cómo funciona ───────────── --}}
    <section id="como-funciona" class="scroll-mt-20 border-t border-line bg-sunken py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <h2 class="max-w-2xl text-balance text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">Del ingreso a la administración en tres pasos</h2>

            <div class="relative mt-14">
                <svg class="pointer-events-none absolute inset-x-0 top-2 hidden h-8 w-full lg:block" viewBox="0 0 1000 32" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M0 16 H170 l8 -4 l8 4 h10 l4 4 l5 -14 l7 20 l4 -10 H500 l8 -4 l8 4 h10 l4 4 l5 -14 l7 20 l4 -10 H1000" class="trace-line" fill="none" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round"/>
                </svg>

                <div class="pointer-events-none absolute bottom-6 left-6 top-6 w-px bg-line lg:hidden" aria-hidden="true"></div>
                <ol class="relative grid gap-10 lg:grid-cols-3 lg:gap-12">
                    @foreach ([
                        ['Ingreso del paciente', 'Registre la hospitalización con peso actual, nivel de triage (crítico, intermedio u observación) y cama asignada.'],
                        ['Prescripción', 'El veterinario programa cada medicamento con dosis, vía y horario. El kárdex se llena solo para todo el día.'],
                        ['Administración confirmada', 'La auxiliar toca la dosis en la tableta, verifica los "5 correctos" y confirma. El inventario se descuenta y todas las pantallas se actualizan.'],
                    ] as $i => [$step, $text])
                        <li class="relative pl-16 lg:pl-0">
                            <span class="pp-num absolute left-0 top-0 z-10 grid size-12 lg:static place-items-center rounded-2xl border border-line bg-surface text-xl font-extrabold text-ink">{{ $i + 1 }}</span>
                            <h3 class="pt-2 text-xl font-bold text-ink lg:mt-5 lg:pt-0">{{ $step }}</h3>
                            <p class="mt-2 max-w-sm text-lg leading-relaxed text-muted">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- ───────────── Para quién ───────────── --}}
    <section id="para-quien" class="scroll-mt-20 border-t border-line py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <h2 class="max-w-2xl text-balance text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">Para equipos que hospitalizan</h2>
            <div class="mt-12 grid gap-x-12 gap-y-10 md:grid-cols-3">
                @foreach ([
                    ['Clínicas veterinarias', 'Con una o dos camas de hospitalización que necesitan dejar atrás el kárdex en papel y el inventario en hojas de cálculo.'],
                    ['Hospitales veterinarios', 'Con varios turnos de enfermería que requieren que todos vean el mismo estado, al mismo tiempo, sin dobles administraciones.'],
                    ['Unidades de cuidado intensivo', 'Con microdosis, infusiones y medicamentos de control especial donde cada 0,01 mL y cada firma cuentan.'],
                ] as [$who, $text])
                    <div class="border-t-2 border-ink pt-5">
                        <h3 class="text-xl font-bold text-ink">{{ $who }}</h3>
                        <p class="mt-2 text-lg leading-relaxed text-muted">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───────────── Contacto ───────────── --}}
    <section id="contacto" class="scroll-mt-20 border-t border-line py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="panel p-8 sm:p-12">
                <div class="grid items-center gap-8 lg:grid-cols-2">
                    <div>
                        <h2 class="text-balance text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">Véalo funcionando con sus propios casos</h2>
                        <p class="mt-4 text-lg leading-relaxed text-muted">Agende una demostración de 30 minutos. Le mostramos el kárdex con pacientes reales de su clínica y resolvemos sus preguntas sobre inventario, control especial y puesta en marcha.</p>
                    </div>
                    <div class="flex flex-col gap-3">
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hola, quiero una demostración de PetPulse para mi clínica.') }}" target="_blank" rel="noopener" class="btn-primary">
                                Escribir por WhatsApp
                            </a>
                        @endif
                        <a href="mailto:{{ $contactEmail }}?subject={{ rawurlencode('Demostración de PetPulse') }}" class="{{ $whatsapp ? 'btn-secondary' : 'btn-primary' }}">
                            Escribir a {{ $contactEmail }}
                        </a>
                        <a href="{{ route('access.create') }}" class="btn-secondary">Solicitar acceso</a>
                        <p class="text-center text-sm text-muted">Respondemos el mismo día hábil.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <x-public-footer />
</x-layouts.public>
