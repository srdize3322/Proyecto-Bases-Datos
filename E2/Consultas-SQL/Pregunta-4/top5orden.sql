--Tipos Pocibles: lo que asumi

SELECT
  COALESCE(NULLIF(TRIM(ar."Tipo"), ''), '(vacío)') AS tipo,
  COUNT(*)                                         AS n_aranceles,
  COUNT(o."IDAtencion")                            AS n_ordenes
FROM "Arancel" ar
LEFT JOIN "Orden" o ON o."IDArancel" = ar."ID"
GROUP BY 1
ORDER BY n_ordenes DESC, n_aranceles DESC;


-- Fonasa 03 y 04 con Tipo vacío o NULL lo que asumi

SELECT
  ar."Codigo",
  ar."ConsAtMedica",
  COALESCE(NULLIF(TRIM(ar."Tipo"), ''), '(vacío)') AS tipo
FROM "Arancel" ar
JOIN "Orden" o ON o."IDArancel" = ar."ID"
WHERE LEFT(ar."Codigo", 2) IN ('03','04')
  AND (ar."Tipo" IS NULL OR TRIM(ar."Tipo") = '')
GROUP BY ar."Codigo", ar."ConsAtMedica", ar."Tipo"
ORDER BY ar."Codigo"
LIMIT 40;


--Top 5 exámenes más solicitados 

WITH examenes AS (
  SELECT
    ar."ConsAtMedica" AS examen,
    COUNT(*)          AS veces
  FROM "Orden" o
  JOIN "Arancel" ar ON ar."ID" = o."IDArancel"
  WHERE
        LOWER(ar."Tipo") LIKE '%examen%'
     OR LEFT(ar."Codigo", 2) IN ('03','04')
  GROUP BY ar."ConsAtMedica"
),
ordenados AS (
  SELECT
    examen,
    veces,
    ROW_NUMBER() OVER (ORDER BY veces DESC, examen) AS rn
  FROM examenes
),
umbral AS (
  SELECT veces
  FROM ordenados
  WHERE rn = 5
)
SELECT
  examen,
  veces AS veces_solicitado
FROM ordenados
WHERE veces >= (SELECT veces FROM umbral)
ORDER BY veces DESC, examen;