<?php

namespace Tests\Feature;

use App\Filament\Pages\AdvisorSupervision;
use App\Filament\Pages\GeneralSettings;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\PostgresTestCase;

class SuperadminAccessTest extends PostgresTestCase
{
    public function test_roles_have_separate_panels_and_privileged_pages_are_protected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $super = User::factory()->create(['is_admin' => true, 'is_superadmin' => true]);
        $this->actingAs($admin, 'web')->get('/admin')->assertOk()->assertDontSee('Configuración general');
        $this->get('/superadmin')->assertRedirect('/superadmin/login');
        $this->actingAs($admin, 'superadmin')->get('/superadmin')->assertForbidden();
        Filament::setCurrentPanel(Filament::getPanel('superadmin'));
        Livewire::test(GeneralSettings::class)->assertForbidden();
        Livewire::test(AdvisorSupervision::class)->assertForbidden();
        $this->actingAs($super, 'superadmin')->get('/superadmin')->assertOk();
        $this->get('/superadmin/configuracion-general')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/superadmin/supervision-asesores')->assertOk();
        $this->actingAs($super, 'web')->get('/admin')->assertForbidden();
        $this->assertFalse($admin->fresh()->is_superadmin);
    }

    public function test_superadmin_login_rejects_admin_and_accepts_superadmin(): void
    {
        $password = 'Aa1'.Str::random(24);
        $admin = User::factory()->create(['is_admin' => true, 'password' => $password]);
        $super = User::factory()->create(['is_admin' => true, 'is_superadmin' => true, 'password' => $password]);
        Filament::setCurrentPanel(Filament::getPanel('superadmin'));
        Livewire::test(Login::class)->fillForm(['email' => $admin->email, 'password' => $password])->call('authenticate')->assertHasFormErrors(['email']);
        $this->assertGuest('superadmin');
        Livewire::test(Login::class)->fillForm(['email' => $super->email, 'password' => $password])->call('authenticate')->assertHasNoFormErrors()->assertRedirect();
        $this->assertAuthenticatedAs($super, 'superadmin');
        $this->assertGuest('web');
    }

    public function test_command_creates_new_superadmin_and_never_promotes_existing_user(): void
    {
        $password = 'Aa1'.Str::random(24);
        $this->artisan('superadmin:create')->expectsQuestion('Nombre', 'Super Prueba')->expectsQuestion('Correo electrónico', 'super@example.test')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, letras y números)', $password)->expectsQuestion('Confirma la contraseña', $password)
            ->expectsOutput('Superadmin creado. Accede a /superadmin.')->assertSuccessful();
        $user = User::where('email', 'super@example.test')->firstOrFail();
        $this->assertTrue($user->is_superadmin && $user->is_admin);
        $this->assertTrue(Hash::check($password, $user->password));
        $admin = User::factory()->create(['is_admin' => true]);
        $this->artisan('superadmin:create')->expectsQuestion('Nombre', 'Intento')->expectsQuestion('Correo electrónico', $admin->email)
            ->expectsOutput('El correo ya existe. No se modificaron sus permisos.')->assertFailed();
        $this->assertFalse($admin->fresh()->is_superadmin);
    }
}
