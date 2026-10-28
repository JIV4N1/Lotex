<?php
namespace Tests\Unit;
use App\Services\LotPolygon;
use PHPUnit\Framework\TestCase;
class LotPolygonTest extends TestCase
{
    public function test_rejects_crossed_degenerate_and_duplicate_vertices(): void
    {
        $this->assertTrue(LotPolygon::valid([[0,0],[10,0],[10,10],[0,10]]));
        $this->assertFalse(LotPolygon::valid([[0,0],[10,10],[0,10],[10,0]]));
        $this->assertFalse(LotPolygon::valid([[0,0],[1,1],[2,2]]));
        $this->assertFalse(LotPolygon::valid([[0,0],[10,0],[10,10],[0,0]]));
    }
}
