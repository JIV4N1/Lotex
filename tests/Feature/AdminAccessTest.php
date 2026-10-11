<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    public function test_guest_is_redirected_to_spanish_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('Acceso')->assertSee('Entrar')
            ->assertSee('lang="es"', false)->assertDontSee('/build/assets/public-');
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_non_admin_is_forbidden_even_locally(): void
    {
        $this->app->instance('env', 'local');
        config(['app.env' => 'local']);
        $user = new User(['name' => 'Test', 'email' => 'user@example.test']);
        $user->id = 1;
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_enter_and_log_out(): void
    {
        $user = new User(['name' => 'Test', 'email' => 'admin@example.test']);
        $user->id = 2;
        $user->is_admin = true;
        $this->actingAs($user)->get('/admin')->assertOk();
        $this->post('/admin/logout')->assertRedirect();
        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_flag_is_not_mass_assignable(): void
    {
        $this->assertFalse((new User)->isFillable('is_admin'));
        $this->assertFalse((new User)->is_admin);
    }
}
