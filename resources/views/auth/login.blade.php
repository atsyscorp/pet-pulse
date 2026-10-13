<x-layouts.public title="Iniciar sesión" description="Acceso al kárdex de hospitalización de PetPulse para el personal de la clínica.">
    <x-public-nav />

    <main class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-14">
        <div class="panel w-full max-w-md p-8">
            <div>
                <svg viewBox="0 0 156 60" class="h-8 w-auto" fill="none" aria-hidden="true">
                    <path d="M0 40 h26 l6 -8 l8 8 h14 l4 6 l6 -36 l8 48 l5 -18 h16 l8 -10 l10 10 h45" class="trace-line" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                </svg>
                <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-ink">Iniciar sesión</h1>
                <p class="mt-1 text-muted">Acceso para el personal de la clínica</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" novalidate>
                @csrf

                <div>
                    <label for="email" class="block text-sm font-semibold text-ink">Correo electrónico</label>
                    <input
                        id="email" name="email" type="email" inputmode="email" autocomplete="username" required autofocus
                        value="{{ old('email') }}"
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        class="field mt-1.5 @error('email') !border-late @enderror"
                    >
                    @error('email')
                        <p id="email-error" class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-ink">Contraseña</label>
                    <input
                        id="password" name="password" type="password" autocomplete="current-password" required
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        class="field mt-1.5 @error('password') !border-late @enderror"
                    >
                    @error('password')
                        <p id="password-error" class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex min-h-12 items-center gap-3 text-sm text-ink">
                    <input type="checkbox" name="remember" value="1" class="size-6 shrink-0 rounded-md border-line accent-[var(--pp-accent)]" @checked(old('remember'))>
                    Mantener la sesión iniciada en este dispositivo
                </label>

                <button type="submit" class="btn-primary w-full">Entrar</button>
            </form>

            <p class="mt-6 border-t border-line pt-6 text-sm text-muted">
                ¿Sin cuenta? El administrador de su clínica crea los accesos del personal. Si su clínica aún no usa PetPulse,
                <a href="{{ route('access.create') }}" class="link-quiet">solicite acceso</a>.
            </p>
        </div>
    </main>
</x-layouts.public>
