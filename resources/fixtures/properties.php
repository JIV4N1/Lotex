<?php

// Fictional properties and specifications. Photos are illustrative, not actual locations.
$rows = [
    ['horizonte-tehuacan', 'HZ-001', 'Un horizonte abierto', 'Tehuacán', 780000, 600, 20, 30, 'disponible', 'campo', true, '2026-09-28'],
    ['jardin-tehuacan', 'HZ-002', 'Espacio para echar raíces', 'Tehuacán', 425000, 250, 10, 25, 'apartado', 'campo', false, '2026-09-16'],
    ['valle-atlixco', 'HZ-003', 'La calma del valle', 'Atlixco', 1250000, 1000, 25, 40, 'disponible', 'campo', true, '2026-09-25'],
    ['sendero-cholula', 'HZ-004', 'Tu siguiente comienzo', 'Cholula', 960000, 320, 16, 20, 'disponible', 'sierra', true, '2026-09-30'],
    ['bosque-zacatlan', 'HZ-005', 'Entre árboles y silencio', 'Zacatlán', 690000, 800, 20, 40, 'disponible', 'bosque', false, '2026-09-20'],
    ['vista-chignahuapan', 'HZ-006', 'Una mirada a la sierra', 'Chignahuapan', 520000, 650, 13, 50, 'vendido', 'sierra', false, '2026-09-10'],
    ['llano-huejotzingo', 'HZ-007', 'Amplitud para tus ideas', 'Huejotzingo', 1480000, 2000, 40, 50, 'disponible', 'campo', false, '2026-09-22'],
    ['refugio-cuetzalan', 'HZ-008', 'Un refugio en el paisaje', 'Cuetzalan', 875000, 1250, 25, 50, 'disponible', 'bosque', false, '2026-09-18'],
];

return array_map(function (array $row): array {
    [$slug, $code, $title, $zone, $price, $area, $frontage, $depth, $availability, $image, $featured, $date] = $row;
    $photos = array_values(array_unique([$image, 'campo', 'sierra', 'bosque']));

    return [
        'slug' => $slug, 'code' => $code, 'title' => $title, 'zone' => $zone,
        'price' => $price, 'area' => $area, 'frontage' => $frontage, 'depth' => $depth,
        'availability' => $availability, 'featured' => $featured, 'date' => $date,
        'images' => array_map(fn ($photo) => [
            'src' => 'images/demo/'.$photo.'.jpg',
            'alt' => match ($photo) {
                'campo' => 'Campos verdes y colinas al atardecer; fotografía ilustrativa',
                'sierra' => 'Paisaje de montañas junto al agua; fotografía ilustrativa',
                default => 'Sendero de bosque iluminado por el sol; fotografía ilustrativa',
            },
        ], $photos),
        'description' => 'Un espacio para imaginar un proyecto a tu medida, con amplitud para disfrutar del entorno. Esta ficha es ficticia: sirve para explorar cómo presentaremos la información de cada terreno. La ubicación, el precio, las medidas y las características no constituyen una oferta real.',
        'services' => ['Electricidad cercana (ejemplo)', 'Agua por verificar (ejemplo)'],
        'access' => 'Camino de acceso — dato de demostración',
        'topography' => 'Pendiente ligera — dato de demostración',
        'land_use' => 'Por verificar; no se acredita un uso autorizado.',
        'documents' => 'Sin documentos reales. Se incorporará información revisada antes de publicar una propiedad.',
        'payment_terms' => 'Por definir con información real. Este prototipo no permite pagos ni apartados.',
    ];
}, $rows);
