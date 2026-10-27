<?php

namespace Tests\Unit;

use App\Catalog\LotMapLayout;
use App\Models\Property;
use PHPUnit\Framework\TestCase;

class LotMapLayoutTest extends TestCase
{
    public function test_santa_martha_croquis_contains_each_of_its_52_lots_once(): void
    {
        $columns = (new LotMapLayout)->for(new Property(['code' => 'SMII']), collect());

        $this->assertNotNull($columns);
        $this->assertSame(52, $this->slotCount($columns));
        $this->assertSame(52, count(array_unique($this->identities($columns))));
        $this->assertSame('1:1', $this->identities($columns)[0]);
        $this->assertContains('2:27', $this->identities($columns));
        $this->assertContains('3:14', $this->identities($columns));
    }

    public function test_villa_de_oshun_croquis_contains_each_of_its_85_lots_once(): void
    {
        $columns = (new LotMapLayout)->for(new Property(['code' => 'VDO183']), collect());

        $this->assertNotNull($columns);
        $this->assertSame(85, $this->slotCount($columns));
        $this->assertSame(85, count(array_unique($this->identities($columns))));
        $this->assertContains('1:47', $this->identities($columns));
        $this->assertContains('2:38', $this->identities($columns));
    }

    public function test_new_published_lot_outside_plan_is_still_shown(): void
    {
        $lot = new Property(['block' => '4', 'lot_number' => '1']);
        $lot->id = 999;
        $columns = (new LotMapLayout)->for(new Property(['code' => 'SMII']), collect([$lot]));

        $this->assertSame('Otros lotes publicados', end($columns)['label']);
        $this->assertSame($lot, end($columns)['lots']->first()['lot']);
    }

    private function slotCount(array $columns): int
    {
        return array_sum(array_map(fn (array $column) => isset($column['lots']) ? $column['lots']->count() : 0, $columns));
    }

    private function identities(array $columns): array
    {
        $identities = [];
        foreach ($columns as $column) {
            foreach ($column['lots'] ?? [] as $slot) {
                $identities[] = $column['block'].':'.$slot['number'];
            }
        }

        return $identities;
    }
}
