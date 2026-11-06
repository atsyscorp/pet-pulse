<x-layouts.public title="Superadministración" description="Acceso restringido al equipo de PetPulse." robots="noindex, nofollow">
    <main class="flex min-h-screen items-center justify-center px-4 py-14">
        <div class="panel w-full max-w-md p-8">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/icon-192.png') }}" alt="" width="40" height="40" class="size-10 rounded-xl">
                <span class="text-xl font-extrabold tracking-tight text-ink">Pet<span class="text-trace">Pulse</span></span>
            </div>
            <h1 class="mt-6 text-3xl font-extrabold tracking-tight text-ink">Superadministración</h1>
            <p class="mt-1 text-muted">Acceso restringido al equipo de PetPulse.</p>

            <form method="POST" action="{{ route('admin.login') }}" class="mt-8 space-y-5" novalidate>
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

                <button type="submit" class="btn-primary w-full">Entrar</button>
            </form>

            <p class="mt-6 border-t border-line pt-6 text-sm text-muted">
                ¿Es personal de una clínica? Ingrese por <a href="{{ route('login') }}" class="link-quiet">el acceso de clínicas</a>.
            </p>
        </div>
    </main>
</x-layouts.public>
