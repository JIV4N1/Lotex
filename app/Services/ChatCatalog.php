<?php

namespace App\Services;

use App\Models\Property;

class ChatCatalog
{
    public const TOPICS = ['measurements', 'area', 'frontage', 'depth', 'price', 'price_per_m2', 'services', 'access', 'topography', 'land_use', 'payment_terms', 'location', 'description', 'availability', 'documents'];

    private function detail(Property $p, array $topics): array
    {
        $p = $p->effective();
        $topics = $topics ?: ['description'];
        if (in_array('measurements', $topics)) {
            $topics = array_merge($topics, ['area', 'frontage', 'depth']);
        }
        $measure = fn ($value, $unit) => $value !== null ? number_format((float) $value, 2).' '.$unit : 'Por confirmar';
        $facts = [
            'area' => 'Superficie: '.$measure($p->area_m2, 'm²'),
            'frontage' => 'Frente: '.$measure($p->frontage_m, 'm'),
            'depth' => 'Fondo: '.$measure($p->depth_m, 'm'),
            'price' => 'Precio: $'.number_format((float) $p->price_mxn, 2).' MXN',
            'price_per_m2' => 'Precio por m²: '.($p->price_per_m2 !== null ? '$'.number_format((float) $p->price_per_m2, 2).' MXN' : 'Por confirmar'),
            'services' => 'Servicios: '.(implode(', ', $p->services ?? []) ?: 'Por confirmar'),
            'access' => 'Acceso: '.($p->access ?: 'Por confirmar'),
            'topography' => 'Topografía: '.($p->topography ?: 'Por confirmar'),
            'land_use' => 'Uso de suelo: '.($p->land_use ?: 'Por confirmar'),
            'payment_terms' => 'Condiciones de pago: '.($p->payment_terms ?: 'Por confirmar'),
            'location' => 'Ubicación: '.implode(', ', [$p->effective()->zone->name, $p->effective()->zone->municipality->name, $p->effective()->zone->municipality->state->name]).($p->reference_address ? '. Referencia: '.$p->reference_address : ''),
            'description' => $p->description ?: 'La descripción está por confirmar.',
            'availability' => 'Disponibilidad: '.$p->availability_status->value,
            'documents' => 'Consulta los documentos públicos en la sección de documentación de esta ficha. El asistente no verifica su validez jurídica.',
        ];
        $lines = [];
        foreach (array_unique($topics) as $topic) {
            if (isset($facts[$topic])) {
                $lines[] = $facts[$topic];
            }
        }

        return ['message' => implode("\n", $lines) ?: '¿Qué dato del terreno te gustaría conocer?', 'properties' => []];
    }

    public function answer(array $filters, ?string $slug): array
    {
        $intent = $filters['intent'];
        if ($intent === 'help') {
            return ['message' => 'Puedo buscar terrenos por zona, presupuesto y superficie, o consultar su ficha. Por ejemplo: terrenos en Tehuacán de hasta un millón de pesos. No gestiono pagos ni confirmo visitas.', 'properties' => []];
        }
        if ($intent === 'visit') {
            return ['message' => 'Para solicitar una visita, abre la ficha del terreno y pulsa Quiero visitar este terreno. Deja tu nombre y WhatsApp; el equipo recibirá tu solicitud y un asesor te contactará para acordar el horario. La solicitud no confirma una cita automáticamente.', 'properties' => []];
        }
        if ($slug && empty($filters['reference']) && in_array($intent, ['details', 'documents']) && Property::publiclyVisible()->where('slug', $slug)->where('is_lotification', true)->exists()) {
            return ['message' => 'Esta ficha agrupa varios lotes con medidas y precios diferentes. Selecciona uno en «Elige tu lote» para consultar sus datos exactos y solicitar una visita. Los documentos comunes están en esta ficha.', 'properties' => []];
        }
        $query = Property::query()->publiclyVisible()->where('is_lotification', false)->with(['zone.municipality.state', 'lotification.zone.municipality.state', 'lotification.images', 'lotification.documents', 'images', 'documents']);
        if (! empty($filters['reference'])) {
            $reference = $filters['reference'];
            $query->where(fn ($q) => $q->where('slug', $reference)->orWhere('code', $reference)->orWhereRaw('LOWER(title) = LOWER(?)', [$reference]));
        } elseif ($slug && in_array($intent, ['details', 'documents'])) {
            $query->where('slug', $slug);
        }
        if (in_array($intent, ['details', 'documents'])) {
            if (empty($filters['reference']) && ! $slug) {
                return ['message' => 'Abre la ficha del terreno que te interesa o dime su título o clave para consultar ese dato.', 'properties' => []];
            }
            $property = $query->first();
            if (! $property) {
                return ['message' => 'Ese terreno no está disponible en el catálogo publicado.', 'properties' => []];
            }

            return $this->detail($property, $intent === 'documents' ? ['documents'] : ($filters['topics'] ?? []));
        }
        if (! empty($filters['location'])) {
            $location = $filters['location'];
            $matchLocation = fn ($q) => $q->whereRaw('LOWER(name) = LOWER(?)', [$location])->orWhereHas('municipality', fn ($m) => $m->whereRaw('LOWER(name) = LOWER(?)', [$location])->orWhereHas('state', fn ($s) => $s->whereRaw('LOWER(name) = LOWER(?)', [$location])));
            $query->where(fn ($q) => $q->whereHas('zone', $matchLocation)->orWhereHas('lotification.zone', $matchLocation));
        }
        foreach (['min_price' => ['price_mxn', '>='], 'max_price' => ['price_mxn', '<='], 'min_area' => ['area_m2', '>='], 'max_area' => ['area_m2', '<=']] as $key => [$column, $operator]) {
            if (isset($filters[$key])) {
                $query->where($column, $operator, $filters[$key]);
            }
        }
        if (! empty($filters['availability'])) {
            $query->where('availability_status', $filters['availability']);
        }
        $total = (clone $query)->count();
        $properties = $query->orderBy('price_mxn')->orderBy('id')->limit(4)->get()->map(fn ($p) => [
            'title' => $p->title, 'url' => route('properties.show', $p->slug),
            'location' => implode(', ', [$p->effective()->zone->name, $p->effective()->zone->municipality->name, $p->effective()->zone->municipality->state->name]),
            'summary' => '$'.number_format((float) $p->price_mxn, 2).' MXN · '.number_format((float) $p->area_m2, 2).' m² · '.$p->availability_status->value,
            'details' => $intent === 'details' ? 'Precio por m²: $'.$p->price_per_m2.' MXN. Frente: '.($p->frontage_m ?? 'Por confirmar').'. Fondo: '.($p->depth_m ?? 'Por confirmar').'. Servicios: '.(implode(', ', $p->services ?? []) ?: 'Por confirmar').'. Acceso: '.($p->access ?: 'Por confirmar').'. Uso de suelo: '.($p->land_use ?: 'Por confirmar').'. Condiciones: '.($p->payment_terms ?: 'Por confirmar') : null,
        ])->all();
        $message = $total ? "Encontré {$total} terreno(s) publicado(s). Muestro hasta cuatro, ordenados por menor precio. Puedes abrir sus fichas para ver toda la información." : 'No encontré terrenos publicados con esos criterios. Prueba otra zona o amplía tu presupuesto o superficie.';
        if ($intent === 'documents') {
            $message .= ' Los documentos públicos disponibles están en cada ficha. Este asistente no certifica su validez jurídica ni garantiza la situación legal del terreno.';
        }

        return compact('message', 'properties');
    }
}
