<?php
namespace App\Services;

class LotPolygon
{
    public static function valid(array $points): bool
    {
        $count = count($points);
        if ($count < 3 || count(array_unique(array_map(fn($p)=>json_encode($p), $points))) !== $count) return false;
        $area = 0;
        for ($i=0; $i<$count; $i++) {
            $a=$points[$i]; $b=$points[($i+1)%$count];
            $area += $a[0]*$b[1]-$b[0]*$a[1];
            for ($j=$i+1; $j<$count; $j++) {
                if ($j === $i+1 || ($i === 0 && $j === $count-1)) continue;
                $c=$points[$j]; $d=$points[($j+1)%$count];
                $cross = fn($p,$q,$r)=>($q[0]-$p[0])*($r[1]-$p[1])-($q[1]-$p[1])*($r[0]-$p[0]);
                $u=$cross($a,$b,$c); $v=$cross($a,$b,$d); $w=$cross($c,$d,$a); $z=$cross($c,$d,$b);
                if ($u*$v < 0 && $w*$z < 0) return false;
            }
        }
        return abs($area) > 0.01;
    }
}
