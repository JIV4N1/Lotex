<?php

namespace Tests\Unit;

use App\Enums\AvailabilityStatus;
use App\Enums\PublicationStatus;
use App\Models\Property;
use PHPUnit\Framework\TestCase;

class PropertyTest extends TestCase
{
    public function test_safe_defaults_and_decimal_price_per_square_meter(): void
    {
        $property = new Property(['price_mxn' => '100.00', 'area_m2' => '3.00']);
        $this->assertSame('33.33', $property->price_per_m2);
        $this->assertSame(PublicationStatus::Draft, $property->publication_status);
        $this->assertSame(AvailabilityStatus::Available, $property->availability_status);
        $this->assertFalse($property->is_featured);
        $this->assertSame([], $property->services);
        $this->assertArrayNotHasKey('price_per_m2', $property->getAttributes());

        $property->price_mxn = '1.00';
        $property->area_m2 = '8.00';
        $this->assertSame('0.13', $property->price_per_m2);
        $property->price_mxn = '0.00';
        $this->assertSame('0.00', $property->price_per_m2);
        $property->area_m2 = '0.00';
        $this->assertNull($property->price_per_m2);
        $this->assertNull((new Property)->price_per_m2);
    }
}
