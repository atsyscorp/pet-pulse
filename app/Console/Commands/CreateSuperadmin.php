<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlatformAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates a superadministrator, or sets a new password for an existing one.
 * The password is only ever typed at the hidden prompt: never an option, so
 * it stays out of shell history and process listings.
 */
final class CreateSuperadmin extends Command
{
    protected $signature = 'petpulse:superadmin
                            {--email= : Correo del superadministrador}
                            {--name= : Nombre (solo al crear la cuenta)}';

    protected $description = 'Crea un superadministrador de PetPulse o cambia su contraseña';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Correo electrónico'))));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:190']])->fails()) {
            $this->error('El correo no es válido.');

            return self::FAILURE;
        }

        $admin = PlatformAdmin::query()->where('email', $email)->first();

        if ($admin !== null && ! $this->confirm("Ya existe un superadministrador con {$email}. ¿Cambiar su contraseña?", true)) {
            return self::SUCCESS;
        }

        $name = $admin?->name ?? trim((string) ($this->option('name') ?: $this->ask('Nombre completo')));

        if ($name === '') {
            $this->error('El nombre es obligatorio.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Contraseña (mínimo 12 caracteres, con mayúsculas, minúsculas y números)');

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::min(12)->mixedCase()->numbers()]],
            [],
            ['password' => 'contraseña'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if ((string) $this->secret('Repita la contraseña') !== $password) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        if ($admin === null) {
            PlatformAdmin::create(['name' => $name, 'email' => $email, 'password' => $password]);
            $this->info('Superadministrador creado. Ingrese en '.route('admin.login'));
        } else {
            $admin->update(['password' => $password]);
            $this->info('Contraseña actualizada.');
        }

        return self::SUCCESS;
    }
}
