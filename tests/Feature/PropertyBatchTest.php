<?php

namespace Tests\Feature;

use App\Filament\Resources\PropertyResource\Pages\ListProperties;
use App\Models\User;
use App\Services\UpdatePropertyBatch;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class PropertyBatchTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    public function test_admin_can_change_selected_lots_from_table(): void
    {
        $one = $this->makeProperty();
        $two = $this->makeProperty(['code' => 'TEST-SECOND', 'slug' => 'second']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ListProperties::class)
            ->callTableBulkAction('availability', [$one, $two], data: ['value' => 'apartado'])
            ->assertHasNoErrors();
        $this->assertSame('apartado', $one->fresh()->availability_status->value);
        $this->assertSame('apartado', $two->fresh()->availability_status->value);
    }

    public function test_batch_updates_only_selected_records_and_preserves_publication(): void
    {
        $one = $this->makeProperty();
        $two = $this->makeProperty(['code' => 'TEST-SECOND', 'slug' => 'second']);
        $admin = User::factory()->create(['is_admin' => true]);
        app(UpdatePropertyBatch::class)->update($admin, [$one->id], 'availability_status', 'apartado');
        $this->assertSame('apartado', $one->fresh()->availability_status->value);
        $this->assertSame('publicado', $one->fresh()->publication_status->value);
        $this->assertSame('disponible', $two->fresh()->availability_status->value);
    }

    public function test_publication_is_atomic_when_one_record_is_incomplete(): void
    {
        $one = $this->makeProperty(['publication_status' => 'borrador']);
        $two = $this->makeProperty(['code' => 'TEST-SECOND', 'slug' => 'second', 'publication_status' => 'borrador', 'area_m2' => null]);
        try {
            app(UpdatePropertyBatch::class)->update(User::factory()->create(['is_admin' => true]), [$one->id, $two->id], 'publication_status', 'publicado');
            $this->fail('Debió rechazar la selección incompleta');
        } catch (ValidationException) {
            $this->assertSame('borrador', $one->fresh()->publication_status->value);
            $this->assertSame('borrador', $two->fresh()->publication_status->value);
        }
    }

    public function test_normal_user_cannot_update_inventory(): void
    {
        $property = $this->makeProperty();
        $this->expectException(AuthorizationException::class);
        app(UpdatePropertyBatch::class)->update(User::factory()->create(['is_admin' => false]), [$property->id], 'availability_status', 'vendido');
    }
}
