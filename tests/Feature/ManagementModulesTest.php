<?php

namespace Tests\Feature;

use App\Filament\Pages\AdvisorSupervision;
use App\Filament\Pages\GeneralSettings;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VisitRequest;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class ManagementModulesTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    public function test_access_and_settings_validation_and_public_output(): void
    {
        foreach (['configuracion-general', 'supervision-asesores'] as $slug) {
            $this->get('/superadmin/'.$slug)->assertRedirect('/superadmin/login');
        }
        $this->actingAs(User::factory()->create(), 'superadmin');
        foreach (['configuracion-general', 'supervision-asesores'] as $slug) {
            $this->get('/superadmin/'.$slug)->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['is_admin' => true, 'is_superadmin' => true]), 'superadmin');
        Filament::setCurrentPanel(Filament::getPanel('superadmin'));
        Livewire::test(GeneralSettings::class)->set('settings.contact_email', 'invalid')->call('save')->assertHasErrors('settings.contact_email');
        $this->assertNull(SiteSetting::find(1));
        Livewire::test(GeneralSettings::class)->set('settings.footer_text', '<script>alert(1)</script>')->set('settings.contact_email', 'equipo@example.com')->set('settings.office_hours', 'Lunes a viernes')->call('save')->assertHasNoErrors();
        $this->get('/')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertSee('equipo@example.com')->assertSee('Lunes a viernes');
        $this->get('/superadmin/configuracion-general')->assertOk();
    }

    public function test_supervision_counts_current_assignments_and_appointments(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_superadmin' => true]);
        $other = User::factory()->create(['is_admin' => true, 'is_superadmin' => true]);
        $p = $this->makeProperty();
        foreach ([
            ['assigned_to' => $admin->id, 'status' => 'nuevo'],
            ['assigned_to' => $admin->id, 'status' => 'contactado'],
            ['assigned_to' => $admin->id, 'status' => 'cita_confirmada', 'appointment_at' => now()->addDay()],
            ['assigned_to' => $admin->id, 'status' => 'cita_confirmada', 'appointment_at' => now()->subDay()],
            ['assigned_to' => $admin->id, 'status' => 'cerrado'],
            ['assigned_to' => null, 'status' => 'nuevo'],
        ] as $values) {
            VisitRequest::create(array_merge(['property_id' => $p->id, 'name' => 'Prueba', 'phone' => '2381234567', 'submission_token' => (string) Str::uuid()], $values));
        }
        $this->actingAs($admin, 'superadmin');
        Filament::setCurrentPanel(Filament::getPanel('superadmin'));
        Livewire::test(AdvisorSupervision::class)->assertViewHas('unassigned', 1)->assertViewHas('advisors', function ($rows) use ($admin, $other) {
            $a = $rows->firstWhere('id', $admin->id);
            $b = $rows->firstWhere('id', $other->id);

            return $a->assigned_visits_count === 5 && $a->new_count === 1 && $a->contacted_count === 1 && $a->upcoming_count === 1 && $a->past_count === 1 && $a->closed_count === 1 && $b->assigned_visits_count === 0;
        });
        $this->get('/superadmin/supervision-asesores')->assertOk();
    }
}
