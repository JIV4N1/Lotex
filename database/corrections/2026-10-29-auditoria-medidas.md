# Revisión visual de cotas — 29 de octubre de 2026

Fuente: imágenes originales indicadas en el manifiesto `2026-10-29-medidas-verificadas.json`. Se revisaron ampliaciones locales. Esto confirma transcripción del plano, no un levantamiento de campo ni una certificación topográfica.

## Corrección SQL existente

Las cotas de los 58 registros incluidos coinciden con las imágenes:

- Santa Martha II, M2 L5–L14 y L18–L27: lados de 8.00 m y 15.00 m; superficie rotulada 120.00 m².
- Villa de Oshun, M1 L5–L23 y L29–L47: lados de 8.00 m y 18.00 m; superficie rotulada 144.00 m².

No se ejecutó el SQL ni se revisó producción en esta auditoría.

## Hallazgos adicionales

La afirmación anterior de que los 79 restantes requieren necesariamente cuatro longitudes diferentes era demasiado general. Santa Martha M1 L2–L10 muestra cotas repetidas 10.42 m y 11.60 m; M3 L3–L13 muestra 8.31 m y 14.43 m. Eso no prueba ángulos rectos ni autoriza redibujarlos como rectángulos.

Villa de Oshun M1, lectura de los lados según su posición en la imagen (NO orientación cardinal ni designación jurídica de frente):

| Lote | Superior (m) | Inferior (m) | Izquierdo (m) | Derecho (m) | Superficie rotulada (m²) |
|---|---:|---:|---:|---:|---:|
| 24 | 20.75 | 20.75 | 6.77 | 6.77 | 140.46 |
| 25 | 10.02 | 9.96 | 14.62 | 13.58 | 140.46 |
| 26 | 10.85 | 10.79 | 13.58 | 12.45 | 140.46 |
| 27 | 7.50 | 7.46 | 19.22 | 18.44 | 140.46 |
| 28 | 7.83 | 7.79 | 18.44 | 17.63 | 140.46 |

El lote 24 limita con la calle a su izquierda en esta imagen: la cota de ese lado es 6.77 m, no 20.75 m. Los lotes 25–28 limitan arriba con la brecha. Los lotes de esquina pueden tener más de un lado hacia vialidades.

Villa de Oshun M2 no tiene un fondo uniforme: por ejemplo L17 presenta 15.12 m arriba y 15.07 m abajo, con laterales rotulados 9.62 m. No se debe publicar 8 × 18 m para esta manzana ni obtener el fondo dividiendo superficie entre frente.

## Reglas para presentar las medidas

- Mantener superficie rotulada, sin recalcularla desde una imagen o desde frente por fondo. Las cotas están redondeadas y los ángulos no tienen por qué ser rectos.
- Para lados distintos, conservar cada longitud; no promediar ni seleccionar arbitrariamente una como fondo único.
- Identificar explícitamente el lado hacia calle/brecha y confirmar la designación de frente en esquinas.
- El modelo actual de dos campos no representa todas las colindancias. No debe declararse completa la corrección de los 137 lotes.
- Las cifras ilegibles y las discrepancias requieren cuadro de medidas del proveedor o plano vectorial. No usar dimensiones en píxeles como medidas físicas.

Pendiente: matriz completa de cuatro lados para los registros fuera del SQL, confirmación de accesos en esquinas e incorporación de esa estructura al sistema. Esta auditoría no cambia medidas publicadas ni marca los 137 lotes como verificados.
