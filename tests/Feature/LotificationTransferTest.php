<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Services\LotificationTransfer;
use Illuminate\Support\Facades\Storage;
use Tests\BuildsPropertyFixtures;
use Tests\PostgresTestCase;

class LotificationTransferTest extends PostgresTestCase
{
    use BuildsPropertyFixtures;

    public function test_package_preserves_lots_media_and_location_without_reusing_ids(): void
    {
        Storage::fake('local');
        $parent = $this->makeProperty(['is_lotification' => true]);
        $lot = $parent->lots()->create(['block' => '1', 'lot_number' => '2', 'price_mxn' => '353000', 'area_m2' => '120.93', 'availability_status' => 'apartado', 'publication_status' => 'publicado']);
        Storage::disk('local')->put('property-images/test.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3ioAAAAASUVORK5CYII='));
        $parent->images()->create(['path' => 'property-images/test.png', 'alt_text' => 'Imagen de prueba', 'position' => 0]);
        $service = app(LotificationTransfer::class);
        $pack = $service->export($parent->code);
        foreach ($pack['records'] as &$row) {
            $row['attributes']['code'] .= '-COPY';
            $row['attributes']['slug'] .= '-copy';
            $row['attributes']['title'] .= ' copia';
        }
        unset($row);
        $count = Property::count();
        $this->assertNull($service->import($pack));
        $this->assertSame($count, Property::count());
        $copy = $service->import($pack, true);
        $this->assertNotSame($copy->id, $parent->id);
        $this->assertSame($parent->zone_id, $copy->zone_id);
        $this->assertSame('apartado', $copy->lots()->sole()->availability_status->value);
        $this->assertSame('120.93', $copy->lots()->sole()->area_m2);
        $this->assertSame('353000.00', $copy->lots()->sole()->price_mxn);
        Storage::disk('local')->assertExists($copy->images()->sole()->path);
        $this->expectException(\RuntimeException::class);
        $service->import($pack, true);
    }

    public function test_rejects_corrupt_media_before_writing_any_records(): void
    {
        $parent = $this->makeProperty(['is_lotification' => true]);
        $parent->lots()->create(['block' => '1', 'lot_number' => '1']);
        $service = app(LotificationTransfer::class);
        $pack = $service->export($parent->code);
        $pack['records'][0]['media'][] = ['content' => base64_encode('<?php echo 1;'), 'sha256' => hash('sha256', '<?php echo 1;'), 'relation' => 'images'];
        $this->expectException(\RuntimeException::class);
        $service->import($pack, true);
    }
}
