<?php

namespace App\Catalog;

use App\Models\Property;
use Illuminate\Support\Collection;

class LotMapLayout
{
    /**
     * Croquis de lectura, basado en la disposición de los planos originales.
     * El orden representa la posición relativa; no contiene geometría catastral.
     */
    public function for(Property $lotification, Collection $publishedLots): ?array
    {
        $columns = match (true) {
            $lotification->code === 'SMII' || str_starts_with((string) $lotification->slug, 'santa-martha-ii') => [
                ['block' => '1', 'label' => 'Manzana 1 · lado poniente', 'numbers' => range(1, 11)],
                ['road' => 'Calle interior'],
                ['block' => '2', 'label' => 'Manzana 2 · lado poniente', 'numbers' => array_merge([1], range(27, 15))],
                ['block' => '2', 'label' => 'Manzana 2 · lado oriente', 'numbers' => range(2, 14)],
                ['road' => 'Calle interior'],
                ['block' => '3', 'label' => 'Manzana 3', 'numbers' => range(1, 14)],
            ],
            $lotification->code === 'VDO183' || str_starts_with((string) $lotification->slug, 'villa-de-oshun') => [
                ['block' => '2', 'label' => 'Manzana 2 · lado poniente', 'numbers' => range(18, 1)],
                ['block' => '2', 'label' => 'Manzana 2 · lado oriente', 'numbers' => range(19, 38)],
                ['road' => 'Calle interior'],
                ['block' => '1', 'label' => 'Manzana 1 · lado poniente', 'numbers' => range(25, 1)],
                ['block' => '1', 'label' => 'Manzana 1 · lado oriente', 'numbers' => range(26, 47)],
            ],
            default => null,
        };

        if ($columns === null) {
            return null;
        }

        $byIdentity = $publishedLots->keyBy(fn (Property $lot) => $lot->block.':'.$lot->lot_number);
        $placed = [];
        foreach ($columns as &$column) {
            if (isset($column['road'])) {
                continue;
            }
            $column['lots'] = collect($column['numbers'])->map(function (int $number) use ($byIdentity, $column, &$placed) {
                $lot = $byIdentity->get($column['block'].':'.$number);
                if ($lot) {
                    $placed[$lot->id] = true;
                }

                return ['number' => $number, 'lot' => $lot];
            });
            unset($column['numbers']);
        }
        unset($column);

        // La vista nunca debe omitir un lote nuevo o renumerado que ya esté publicado.
        $other = $publishedLots->reject(fn (Property $lot) => isset($placed[$lot->id]));
        if ($other->isNotEmpty()) {
            $columns[] = ['block' => 'Otros', 'label' => 'Otros lotes publicados', 'lots' => $other->map(fn (Property $lot) => ['number' => $lot->lot_number, 'lot' => $lot])];
        }

        return $columns;
    }
}
