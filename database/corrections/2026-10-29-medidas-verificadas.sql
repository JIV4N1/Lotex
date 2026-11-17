-- Medidas transcritas de los planos proporcionados, 2026-10-29.
-- SOLO 58 lotes de lados uniformes. No resuelve los otros 79 lotes.
-- Ejecutar completo en la base correspondiente, después de respaldarla.
-- Si falta un lote, cambia su identidad/superficie o hay medidas distintas, cancela todo.
-- Repetible: no sobrescribe medidas existentes y no cambia publicación ni disponibilidad.
BEGIN;
SET LOCAL lock_timeout = '5s';
CREATE TEMP TABLE lot_measurements_patch (
 parent_code text, code text PRIMARY KEY, block text, lot_number text,
 area_m2 numeric(14,2), frontage_m numeric(12,2), depth_m numeric(12,2)
) ON COMMIT DROP;
INSERT INTO lot_measurements_patch VALUES
('SMII', 'SMII-M2-L5', '2', '5', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L6', '2', '6', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L7', '2', '7', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L8', '2', '8', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L9', '2', '9', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L10', '2', '10', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L11', '2', '11', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L12', '2', '12', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L13', '2', '13', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L14', '2', '14', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L18', '2', '18', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L19', '2', '19', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L20', '2', '20', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L21', '2', '21', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L22', '2', '22', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L23', '2', '23', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L24', '2', '24', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L25', '2', '25', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L26', '2', '26', 120.00, 8.00, 15.00),
('SMII', 'SMII-M2-L27', '2', '27', 120.00, 8.00, 15.00),
('VDO183', 'VDO183-M1-L5', '1', '5', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L6', '1', '6', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L7', '1', '7', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L8', '1', '8', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L9', '1', '9', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L10', '1', '10', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L11', '1', '11', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L12', '1', '12', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L13', '1', '13', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L14', '1', '14', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L15', '1', '15', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L16', '1', '16', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L17', '1', '17', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L18', '1', '18', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L19', '1', '19', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L20', '1', '20', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L21', '1', '21', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L22', '1', '22', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L23', '1', '23', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L29', '1', '29', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L30', '1', '30', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L31', '1', '31', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L32', '1', '32', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L33', '1', '33', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L34', '1', '34', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L35', '1', '35', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L36', '1', '36', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L37', '1', '37', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L38', '1', '38', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L39', '1', '39', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L40', '1', '40', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L41', '1', '41', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L42', '1', '42', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L43', '1', '43', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L44', '1', '44', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L45', '1', '45', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L46', '1', '46', 144.00, 8.00, 18.00),
('VDO183', 'VDO183-M1-L47', '1', '47', 144.00, 8.00, 18.00);
LOCK TABLE properties IN SHARE ROW EXCLUSIVE MODE;
DO $validate$
BEGIN
 IF (SELECT count(*) FROM lot_measurements_patch m
     JOIN properties p ON p.code=m.code AND p.deleted_at IS NULL AND NOT p.is_lotification
     JOIN properties parent ON parent.id=p.lotification_id AND parent.code=m.parent_code
       AND parent.is_lotification AND parent.deleted_at IS NULL
     WHERE p.block=m.block AND p.lot_number=m.lot_number AND p.area_m2=m.area_m2) <> 58 THEN
   RAISE EXCEPTION 'Inventario diferente: se requieren los 58 lotes con su lotificación, manzana, número y superficie originales. No se modificó ningún lote.';
 END IF;
 IF EXISTS (SELECT 1 FROM properties p JOIN lot_measurements_patch m ON p.code=m.code
   WHERE (p.frontage_m IS NOT NULL AND p.frontage_m<>m.frontage_m)
      OR (p.depth_m IS NOT NULL AND p.depth_m<>m.depth_m)) THEN
   RAISE EXCEPTION 'Hay medidas existentes diferentes del plano: revisar antes de sobrescribir. No se modificó ningún lote.';
 END IF;
END $validate$;
UPDATE properties p SET frontage_m=COALESCE(p.frontage_m,m.frontage_m),
 depth_m=COALESCE(p.depth_m,m.depth_m), updated_at=NOW()
FROM lot_measurements_patch m WHERE p.code=m.code
 AND (p.frontage_m IS NULL OR p.depth_m IS NULL);
SELECT m.parent_code AS lotificacion, count(*) AS lotes_verificados,
 min(p.frontage_m) AS frente_m, min(p.depth_m) AS fondo_m
FROM lot_measurements_patch m JOIN properties p ON p.code=m.code GROUP BY m.parent_code;
COMMIT;
