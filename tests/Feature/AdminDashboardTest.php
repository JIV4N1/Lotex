<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\VisitRequest;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class AdminDashboardTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    public function test_dashboard_shows_real_requests_and_admin_only_assets(): void
    {
        $property = $this->makeProperty();
        VisitRequest::create(['property_id' => $property->id, 'name' => 'Cliente de prueba del panel', 'phone' => '522381234567', 'submission_token' => (string) \Illuminate\Support\Str::uuid()]);
        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.test']);
        $admin->id = 1;
        $admin->is_admin = true;
        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('Últimos interesados')->assertSee('Cliente de prueba del panel')
            ->assertSee('Sin asignar')->assertSee('Gestión comercial')->assertSee('Ubicaciones')
            ->assertSee('/build/assets/admin-', false)->assertDontSee('/build/assets/public-', false);
    }
}
