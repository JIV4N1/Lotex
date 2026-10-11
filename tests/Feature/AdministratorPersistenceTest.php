<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\PostgresTestCase;

class AdministratorPersistenceTest extends PostgresTestCase
{
    public function test_command_creates_a_hashed_administrator_and_refuses_existing_email(): void
    {
        $password = 'Aa1'.Str::random(24);
        $this->artisan('admin:create')
            ->expectsQuestion('Nombre', 'Prueba')
            ->expectsQuestion('Correo electrónico', 'new-admin@example.test')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, letras y números)', $password)
            ->expectsQuestion('Confirma la contraseña', $password)
            ->expectsOutput('Administrador creado. Accede a /admin.')
            ->assertSuccessful();
        $admin = User::where('email', 'new-admin@example.test')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertNotSame($password, $admin->getRawOriginal('password'));
        $this->assertTrue(Hash::check($password, $admin->password));

        $normal = User::create(['name' => 'Normal', 'email' => 'normal@example.test', 'password' => $password]);
        $this->artisan('admin:create')
            ->expectsQuestion('Nombre', 'Intento')
            ->expectsQuestion('Correo electrónico', 'NORMAL@example.test')
            ->expectsOutput('El correo ya existe. No se modificaron sus permisos.')
            ->assertFailed();
        $this->assertFalse($normal->fresh()->is_admin);
        $this->assertSame('Normal', $normal->fresh()->name);
    }

    public function test_login_rejects_normal_user_and_accepts_admin(): void
    {
        $password = 'Aa1'.Str::random(24);
        $user = User::create(['name' => 'Prueba', 'email' => 'login@example.test', 'password' => $password]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => $password])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
        $this->assertGuest();
        $user->is_admin = true;
        $user->save();
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => $password])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk();
    }

    public function test_command_rejects_mismatched_passwords(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Nombre', 'Prueba')
            ->expectsQuestion('Correo electrónico', 'invalid@example.test')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, letras y números)', 'Aa1'.Str::random(24))
            ->expectsQuestion('Confirma la contraseña', 'Aa1'.Str::random(24))
            ->expectsOutput('La contraseña no cumple los requisitos o no coincide.')
            ->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.test']);
    }
}
