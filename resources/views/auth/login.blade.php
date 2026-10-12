<x-layouts.public title="Iniciar sesión" description="Acceso al kárdex de hospitalización de PetPulse para el personal de la clínica.">
    <x-public-nav />

    <main class="relative flex min-h-[calc(100vh-4rem)] items-center justify-center overflow-hidden px-4 py-16">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -left-40 top-0 size-[28rem] rounded-full bg-neon-violet/25 blur-3xl"></div>
            <div class="absolute -right-40 bottom-0 size-[28rem] rounded-full bg-neon-cyan/20 blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-md rounded-[2rem] border border-white/10 bg-white/[0.04] p-8 shadow-2xl backdrop-blur">
            <div class="flex flex-col items-center text-center">
                <img src="{{ asset('images/icon-192.png') }}" alt="" width="72" height="72" class="size-18 rounded-2xl">
                <h1 class="mt-4 text-2xl font-black text-white">Iniciar sesión</h1>
                <p class="mt-1 text-sm text-slate-400">Acceso para el personal de la clínica</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" novalidate>
                @csrf

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-200">Correo electrónico</label>
                    <input
                        id="email" name="email" type="email" inputmode="email" autocomplete="username" required autofocus
                        value="{{ old('email') }}"
                        class="input-neon mt-1 @error('email') ring-red-400 @enderror"
                    >
                    @error('email')
                        <p class="mt-2 text-sm font-semibold text-red-300" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-200">Contraseña</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="input-neon mt-1">
                    @error('password')
                        <p class="mt-2 text-sm font-semibold text-red-300" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-3 text-sm text-slate-300">
                    <input type="checkbox" name="remember" value="1" class="size-5 rounded border-white/30 bg-transparent text-neon-cyan focus:ring-neon-cyan" @checked(old('remember'))>
                    Mantener la sesión iniciada en este dispositivo
                </label>

                <button type="submit" class="btn-neon w-full justify-center py-3.5 text-base">Entrar</button>
            </form>

            <p class="mt-6 text-center text-xs text-slate-500">
                ¿Sin cuenta? El administrador de su clínica crea los accesos del personal.
            </p>
        </div>
    </main>
</x-layouts.public>
