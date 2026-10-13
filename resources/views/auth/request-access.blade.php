<x-layouts.public title="Solicitar acceso" description="Solicite acceso a PetPulse para su clínica veterinaria. Revisamos cada solicitud y le escribimos el mismo día hábil.">
    <x-public-nav />

    @php
        $input = 'field mt-1.5';
        $invalid = fn (string $name) => $errors->has($name) ? '!border-late' : '';
    @endphp

    <main class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16 lg:py-20">
        <div>
            <h1 class="text-balance text-4xl font-extrabold leading-[1.1] tracking-tight text-ink sm:text-5xl">Solicitar acceso</h1>
            <p class="mt-5 max-w-md text-lg leading-relaxed text-muted">
                Cuéntenos sobre su clínica. Revisamos cada solicitud, le escribimos el mismo día hábil
                y activamos los accesos de su equipo.
            </p>
            <p class="mt-8 max-w-md text-sm text-muted">
                ¿Ya tiene cuenta? <a href="{{ route('login') }}" class="link-quiet">Iniciar sesión</a>
            </p>
        </div>

        @if (session('access_requested'))
            <section class="panel self-start p-8 sm:p-10" role="status" aria-live="polite">
                <span class="grid size-12 place-items-center rounded-2xl bg-ok-soft text-ok" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg>
                </span>
                <h2 class="mt-5 text-2xl font-bold text-ink">Recibimos su solicitud</h2>
                <p class="mt-2 text-lg leading-relaxed text-muted">
                    Le escribiremos a <strong class="text-ink">{{ session('access_requested') }}</strong> el mismo día hábil.
                </p>
                <a href="{{ route('home') }}" class="btn-secondary mt-8">Volver al inicio</a>
            </section>
        @else
            <form method="POST" action="{{ route('access.store') }}" class="panel self-start p-6 sm:p-10" novalidate>
                @csrf

                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-late bg-late-soft px-4 py-3 text-sm font-semibold text-late" role="alert">
                        Revise los campos marcados para enviar la solicitud.
                    </div>
                @endif

                {{-- Honeypot: people never see or fill this. --}}
                <div class="absolute -left-[9999px]" aria-hidden="true">
                    <label for="website">No complete este campo</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <fieldset>
                    <legend class="text-xl font-bold text-ink">La clínica</legend>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="clinic_name" class="block text-sm font-semibold text-ink">Nombre de la clínica</label>
                            <input id="clinic_name" name="clinic_name" type="text" required autocomplete="organization" value="{{ old('clinic_name') }}" maxlength="150"
                                @error('clinic_name') aria-invalid="true" aria-describedby="clinic_name-error" @enderror class="{{ $input }} {{ $invalid('clinic_name') }}">
                            @error('clinic_name') <p id="clinic_name-error" class="mt-2 text-sm font-semibold text-late">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="city" class="block text-sm font-semibold text-ink">Ciudad</label>
                            <input id="city" name="city" type="text" required autocomplete="address-level2" value="{{ old('city') }}" maxlength="100"
                                @error('city') aria-invalid="true" aria-describedby="city-error" @enderror class="{{ $input }} {{ $invalid('city') }}">
                            @error('city') <p id="city-error" class="mt-2 text-sm font-semibold text-late">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="tax_id" class="block text-sm font-semibold text-ink">NIT <span class="font-normal text-muted">(opcional)</span></label>
                            <input id="tax_id" name="tax_id" type="text" inputmode="numeric" value="{{ old('tax_id') }}" maxlength="30"
                                @error('tax_id') aria-invalid="true" aria-describedby="tax_id-error" @enderror class="{{ $input }} {{ $invalid('tax_id') }}">
                            @error('tax_id') <p id="tax_id-error" class="mt-2 text-sm font-semibold text-late">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="beds" class="block text-sm font-semibold text-ink">Camas de hospitalización <span class="font-normal text-muted">(opcional)</span></label>
                            <input id="beds" name="beds" type="number" inputmode="numeric" min="1" max="500" value="{{ old('beds') }}"
                                @error('beds') aria-invalid="true" aria-describedby="beds-error" @enderror class="{{ $input }} {{ $invalid('beds') }}">
                            @error('beds') <p id="beds-error" class="mt-2 text-sm font-semibold text-late">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>

                <fieldset class="mt-10">
                    <legend class="text-xl font-bold text-ink">Contacto</legend>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="contact_name" class="block text-sm font-semibold text-ink">Su nombre</label>
                            <input id="contact_name" name="contact_name" type="text" required autocomplete="name" value="{{ old('contact_name') }}" maxlength="120"
                                @error('contact_name') aria-invalid="true" aria-describedby="contact_name-error" @enderror class="{{ $input }} {{ $invalid('contact_name') }}">
                            @error('contact_name') <p id="contact_name-error" class="mt-2 text-sm font-semibold text-late">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-semibold text-ink">Correo electrónico</label>
                            <input id="email" name="email" type="email" inputmode="email" required autocomplete="email" value="{{ old('email') }}" maxlength="190"
                                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror class="{{ $input }} {{ $invalid('email') }}">
                            @error('email') <p id="email-error" class="mt-2 text-sm font-semibold text-late">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-ink">Teléfono o WhatsApp</label>
                            <input id="phone" name="phone" type="tel" inputmode="tel" required autocomplete="tel" value="{{ old('phone') }}" maxlength="30"
                                @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror class="{{ $input }} {{ $invalid('phone') }}">
                            @error('phone') <p id="phone-error" class="mt-2 text-sm font-semibold text-late">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="message" class="block text-sm font-semibold text-ink">¿Algo que debamos saber? <span class="font-normal text-muted">(opcional)</span></label>
                            <textarea id="message" name="message" rows="4" maxlength="1000"
                                @error('message') aria-invalid="true" aria-describedby="message-error" @enderror class="{{ $input }} {{ $invalid('message') }}">{{ old('message') }}</textarea>
                            @error('message') <p id="message-error" class="mt-2 text-sm font-semibold text-late">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>

                <div class="mt-8">
                    <label class="flex min-h-12 items-start gap-3 text-sm leading-relaxed text-ink">
                        <input type="checkbox" name="consent" value="1" required class="mt-0.5 size-6 shrink-0 rounded-md border-line accent-[var(--pp-accent)]" @checked(old('consent'))
                            @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror>
                        <span>Autorizo a PetPulse a tratar estos datos para responder mi solicitud, conforme a la Ley 1581 de 2012.</span>
                    </label>
                    @error('consent') <p id="consent-error" class="mt-2 text-sm font-semibold text-late">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn-primary mt-8 w-full sm:w-auto">Enviar solicitud</button>
            </form>
        @endif
    </main>

    <x-public-footer />
</x-layouts.public>
