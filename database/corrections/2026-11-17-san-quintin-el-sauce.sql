-- Dos publicaciones informativas, según datos corregidos el 17/11/2026.
-- Requiere migraciones del catálogo informativo y pagos semanales.
-- Patzingo se ubica en Ajalpan, Puebla. El punto exacto queda por incorporar.
BEGIN;
SET LOCAL lock_timeout = '10s';
DO $importacion$
DECLARE
    ficha record;
    estado_id bigint;
    municipio_id bigint;
    zona_id bigint;
    propiedad_id bigint;
    coincidencias integer;
BEGIN
    LOCK TABLE properties IN SHARE ROW EXCLUSIVE MODE;
    INSERT INTO states(name, created_at, updated_at) VALUES ('Puebla', NOW(), NOW()) ON CONFLICT(name) DO NOTHING;
    SELECT id INTO STRICT estado_id FROM states WHERE name = 'Puebla';
    FOR ficha IN SELECT * FROM (VALUES
        ('SAN-QUINTIN-120000', 'san-quintin', 'San Quintín', 'Ajalpan', 'Patzingo', 120000.00, 250.00, 'SAN-QUINTIN-120000', 'san-quintin'),
        ('EL-SAUCE-75000', 'el-sauce-san-jose-miahuatlan', 'El Sauce', 'San José Miahuatlán', 'San José Miahuatlán', 75000.00, 200.00, 'ZIN-UBBJ-75500', 'zinacantepec-cerca-de-ubbj')
    ) AS datos(codigo, slug, titulo, municipio, zona, precio, semanal, codigo_anterior, slug_anterior)
    LOOP
        INSERT INTO municipalities(state_id, name, created_at, updated_at) VALUES (estado_id, ficha.municipio, NOW(), NOW()) ON CONFLICT(state_id,name) DO NOTHING;
        SELECT id INTO STRICT municipio_id FROM municipalities WHERE state_id = estado_id AND name = ficha.municipio;
        INSERT INTO zones(municipality_id,name,created_at,updated_at) VALUES (municipio_id,ficha.zona,NOW(),NOW()) ON CONFLICT(municipality_id,name) DO NOTHING;
        SELECT id INTO STRICT zona_id FROM zones WHERE municipality_id = municipio_id AND name = ficha.zona;
        SELECT COUNT(*), MIN(id) INTO coincidencias, propiedad_id FROM properties
        WHERE code IN (ficha.codigo, ficha.codigo_anterior) OR slug IN (ficha.slug,ficha.slug_anterior);
        IF coincidencias > 1 THEN
            RAISE EXCEPTION 'Hay varias fichas para %. Revisa duplicados antes de importar.', ficha.titulo;
        END IF;
        IF propiedad_id IS NOT NULL AND EXISTS(SELECT 1 FROM properties WHERE id=propiedad_id AND (deleted_at IS NOT NULL OR NOT is_lotification OR lotification_id IS NOT NULL)) THEN
            RAISE EXCEPTION 'La referencia de % pertenece a un registro eliminado o de otro tipo.', ficha.titulo;
        END IF;
        IF propiedad_id IS NULL THEN
            INSERT INTO properties(code,slug,title,is_lotification,publication_status,created_at,updated_at)
            VALUES(ficha.codigo,ficha.slug,ficha.titulo,TRUE,'borrador',NOW(),NOW()) RETURNING id INTO propiedad_id;
        END IF;
        UPDATE properties SET code=ficha.codigo, slug=ficha.slug, title=ficha.titulo,
            description=format('Lotificación %s, ubicada en %s, Puebla. Terrenos de 8 por 18 metros (144 m²), con enganche de $500 MXN y pagos semanales. Solicita una visita para conocer el lugar y consultar las opciones disponibles.',ficha.titulo,ficha.zona),
            zone_id=zona_id, price_mxn=ficha.precio, area_m2=144.00, area_max_m2=144.00,
            frontage_m=8.00, depth_m=18.00, down_payment_mxn=500.00,
            weekly_payment_mxn=ficha.semanal, monthly_payment_mxn=NULL,
            payment_terms=format('Precio normal $%s MXN. Enganche de $500 MXN y pagos semanales de $%s MXN. Confirma el calendario, plazo y condiciones con un asesor.',to_char(ficha.precio,'FM999,999,999.00'),to_char(ficha.semanal,'FM999,999,999.00')),
            reference_address=ficha.zona || ', Puebla. Ubicación exacta pendiente de incorporar.',
            publication_status='publicado', development_status='consultas', is_featured=TRUE, updated_at=NOW()
        WHERE id=propiedad_id;
    END LOOP;
END;
$importacion$;
SELECT code,title,price_mxn,area_m2,frontage_m,depth_m,down_payment_mxn,weekly_payment_mxn,publication_status
FROM properties WHERE code IN ('SAN-QUINTIN-120000','EL-SAUCE-75000');
COMMIT;
