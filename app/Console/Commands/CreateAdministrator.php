<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Crea un administrador nuevo sin modificar usuarios existentes';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Este comando requiere una terminal interactiva.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Nombre'));
        $email = mb_strtolower(trim((string) $this->ask('Correo electrónico')));
        $validator = Validator::make(compact('name', 'email'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);
        if ($validator->fails()) {
            $this->error('Nombre o correo inválido. Intenta de nuevo.');

            return self::FAILURE;
        }

        try {
            if (User::whereRaw('lower(email) = ?', [$email])->exists()) {
                $this->error('El correo ya existe. No se modificaron sus permisos.');

                return self::FAILURE;
            }

            // Disable the visible-input fallback if the terminal cannot hide input.
            $password = $this->secret('Contraseña (mínimo 12 caracteres, letras y números)', false);
            $confirmation = $this->secret('Confirma la contraseña', false);
            $validator = Validator::make([
                'password' => $password,
                'password_confirmation' => $confirmation,
            ], ['password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);

            if ($validator->fails()) {
                $this->error('La contraseña no cumple los requisitos o no coincide.');

                return self::FAILURE;
            }

            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->is_admin = true;
            $user->save(); // User's hashed cast handles password hashing.
        } catch (QueryException) {
            $this->error('No se pudo crear el administrador. Revisa PostgreSQL, las migraciones y que el correo no exista.');

            return self::FAILURE;
        }

        $this->info('Administrador creado. Accede a /admin.');

        return self::SUCCESS;
    }
}
