<?php

namespace Tests\Feature;

use App\Models\Municipality;
use App\Models\Property;
use App\Models\State;
use App\Models\Zone;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\PostgresTestCase;

class PropertyPersistenceTest extends PostgresTestCase
{
    private function property(): Property
    {
        $state = State::create(['name' => 'Estado de prueba']);
        $municipality = $state->municipalities()->create(['name' => 'Municipio de prueba']);
        $zone = $municipality->zones()->create(['name' => 'Zona de prueba']);

        return $zone->properties()->create([
            'code' => 'TEST-1', 'slug' => 'test-1', 'title' => 'Prueba',
            'description' => 'Solo para pruebas transaccionales', 'price_mxn' => '1000000.00', 'area_m2' => '250.00',
        ]);
    }

    public function test_relationships_cover_order_and_soft_deletion(): void
    {
        $property = $this->property();
        $this->assertSame('Estado de prueba', $property->zone->municipality->state->name);
        $this->assertTrue($property->zone->properties->first()->is($property));
        $this->assertSame('4000.00', $property->price_per_m2);
        $last = $property->images()->create(['path' => 'test/last.jpg', 'alt_text' => 'Última', 'position' => 5]);
        $first = $property->images()->create(['path' => 'test/first.jpg', 'alt_text' => 'Primera', 'position' => 0]);
        $property->images()->create(['path' => 'test/tie.jpg', 'alt_text' => 'Empate', 'position' => 0]);
        $this->assertTrue($property->coverImage->is($first));
        $this->assertTrue($property->images->first()->is($first));
        $this->assertTrue($last->property->is($property));
        $property->delete();
        $this->assertNull(Property::find($property->id));
        $this->assertNotNull(Property::withTrashed()->find($property->id));
        $this->assertDatabaseHas('property_images', ['id' => $first->id]);
    }

    #[DataProvider('invalidValues')]
    public function test_database_rejects_invalid_values(string $field, mixed $value): void
    {
        $property = $this->property();
        $this->expectException(QueryException::class);
        // Query builder deliberately bypasses model casts to exercise PostgreSQL constraints.
        Property::whereKey($property->id)->update([$field => $value]);
    }

    public static function invalidValues(): array
    {
        return [['price_mxn', '-0.01'], ['area_m2', '0'], ['area_m2', '-1'], ['publication_status', 'invalid'], ['availability_status', 'invalid'], ['zone_id', 999999999], ['latitude', '91'], ['longitude', '-181']];
    }

    #[DataProvider('locations')]
    public function test_locations_with_dependents_cannot_be_deleted(string $model): void
    {
        $property = $this->property();
        $property->delete();
        $this->expectException(QueryException::class);
        $model::query()->firstOrFail()->delete();
    }

    public static function locations(): array
    {
        return [[State::class], [Municipality::class], [Zone::class]];
    }

    #[DataProvider('uniqueFields')]
    public function test_property_identifiers_remain_unique_after_soft_delete(string $field): void
    {
        $property = $this->property();
        $copy = $property->replicate();
        $copy->code = $field === 'code' ? $property->code : 'OTHER';
        $copy->slug = $field === 'slug' ? $property->slug : 'other';
        $property->delete();
        $this->expectException(QueryException::class);
        $copy->save();
    }

    public static function uniqueFields(): array
    {
        return [['code'], ['slug']];
    }
}
