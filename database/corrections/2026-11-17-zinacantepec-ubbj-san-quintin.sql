-- Lotex. Dos fichas generales. Requiere la migración del catálogo informativo.
-- Borradores para confirmar el precio de Zinacantepec y la ubicación de San Quintín.
BEGIN;
SET LOCAL lock_timeout = '10s';
DO $importacion$
DECLARE
    estado_id bigint;
    municipio_id bigint;
    zona_id bigint;
BEGIN
    LOCK TABLE properties IN SHARE ROW EXCLUSIVE MODE;
    IF EXISTS (
        SELECT 1 FROM properties
        WHERE code IN ('ZIN-UBBJ-75500', 'SAN-QUINTIN-120000')
           OR slug IN ('zinacantepec-cerca-de-ubbj', 'san-quintin')
    ) THEN
        RAISE EXCEPTION 'Una de estas publicaciones ya existe. La importación se canceló para evitar duplicados.';
    END IF;

    INSERT INTO states (name, created_at, updated_at)
    VALUES ('Puebla', NOW(), NOW()) ON CONFLICT (name) DO NOTHING;
    SELECT id INTO STRICT estado_id FROM states WHERE name = 'Puebla';
    INSERT INTO municipalities (state_id, name, created_at, updated_at)
    VALUES (estado_id, 'Zinacatepec', NOW(), NOW()) ON CONFLICT (state_id, name) DO NOTHING;
    SELECT id INTO STRICT municipio_id FROM municipalities WHERE state_id = estado_id AND name = 'Zinacatepec';
    INSERT INTO zones (municipality_id, name, created_at, updated_at)
    VALUES (municipio_id, 'Zinacantepec', NOW(), NOW()) ON CONFLICT (municipality_id, name) DO NOTHING;
    SELECT id INTO STRICT zona_id FROM zones WHERE municipality_id = municipio_id AND name = 'Zinacantepec';

    INSERT INTO properties (
        code, slug, title, description, zone_id, is_lotification,
        price_mxn, area_m2, area_max_m2, frontage_m, depth_m,
        down_payment_mxn, monthly_payment_mxn, payment_terms, reference_address,
        publication_status, development_status, availability_status, is_featured, services,
        created_at, updated_at
    ) VALUES (
        'ZIN-UBBJ-75500', 'zinacantepec-cerca-de-ubbj', 'Lotes cerca de la UBBJ en Zinacantepec',
        'Lotificación en Zinacantepec con terrenos de 8 por 18 metros (144 m²). Como referencia, se encuentra cerca de unas granjas y de la universidad de medicina UBBJ, según la información proporcionada. Solicita una visita para conocer la ubicación y las opciones disponibles.',
        zona_id, TRUE, 75500.00, 144.00, 144.00, 8.00, 18.00,
        500.00, NULL,
        'Precio informado: $75,500 MXN. Enganche de $500 MXN y pagos semanales de $200 MXN. Confirma el plazo, calendario y condiciones de contratación con un asesor.',
        'Cerca de unas granjas y de la universidad de medicina UBBJ en Zinacantepec, según referencia proporcionada. Punto exacto pendiente de incorporar.',
        'borrador', 'consultas', 'disponible', TRUE, '[]'::jsonb, NOW(), NOW()
    ), (
        'SAN-QUINTIN-120000', 'san-quintin', 'Lotes en San Quintín',
        'Lotificación en San Quintín con terrenos de 8 por 18 metros (144 m²). Enganche de $500 MXN y pagos semanales de $250 MXN. Consulta con un asesor las opciones y condiciones disponibles.',
        NULL, TRUE, 120000.00, 144.00, 144.00, 8.00, 18.00,
        500.00, NULL,
        'Precio informado: $120,000 MXN. Enganche de $500 MXN y pagos semanales de $250 MXN. Confirma el plazo, calendario y condiciones de contratación con un asesor.',
        'San Quintín. Municipio, estado y punto exacto pendientes de confirmar.',
        'borrador', 'consultas', 'disponible', TRUE, '[]'::jsonb, NOW(), NOW()
    );
END;
$importacion$;
SELECT code, title, publication_status, price_mxn, area_m2, down_payment_mxn, payment_terms
FROM properties WHERE code IN ('ZIN-UBBJ-75500', 'SAN-QUINTIN-120000');
COMMIT;
