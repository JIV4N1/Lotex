<?php

namespace Tests;

use App\Models\Municipality;
use App\Models\Property;
use App\Models\State;
use App\Models\Zone;

trait BuildsPropertyFixtures
{
    protected function makeProperty(array $attributes = []): Property
    {
        $state = State::firstOrCreate(['name' => 'Puebla']);
        $municipality = Municipality::firstOrCreate(['name' => 'Tehuacán', 'state_id' => $state->id]);
        $zone = Zone::firstOrCreate(['name' => 'Centro', 'municipality_id' => $municipality->id]);

        return Property::create(array_merge([
            'zone_id' => $zone->id, 'code' => 'TEST-CRUD', 'slug' => 'terreno-de-prueba',
            'title' => 'Terreno de prueba', 'description' => 'Descripción del terreno',
            'price_mxn' => '780000.00', 'area_m2' => '600.00',
            'frontage_m' => '20.00', 'depth_m' => '30.00',
            'publication_status' => 'publicado', 'services' => ['Agua'],
        ], $attributes));
    }
}
