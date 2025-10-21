WITH conteo AS (
  SELECT "Medicamento", COUNT(*) AS veces
  FROM medicamentos
  GROUP BY "Medicamento"
),
ordenados AS (
  SELECT "Medicamento", veces,
         ROW_NUMBER() OVER (ORDER BY veces DESC, "Medicamento") AS rn
  FROM conteo
),
umbral AS (
  SELECT veces FROM ordenados WHERE rn = 5
)
SELECT "Medicamento", veces AS veces_recetado
FROM ordenados
WHERE veces >= (SELECT veces FROM umbral)
ORDER BY veces DESC, "Medicamento";
