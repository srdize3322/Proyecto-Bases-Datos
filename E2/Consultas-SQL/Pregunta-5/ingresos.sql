-- Preguta 5

WITH
-- Pueden haver dupliados
por_atencion AS (
  SELECT
    a."ID"                AS id_atencion,
    BOOL_OR(a."Efectuada") AS efectuada,
    MIN(a.fecha)          AS fecha_base,
    MAX(a."IDPaciente")   AS id_paciente
  FROM "Atencion" a
  GROUP BY a."ID"
),
paciente_con_institucion AS (
  SELECT
    p."ID" AS id_paciente,
    i."Nombre" AS nombre_institucion,
    COALESCE(i."Nombre", 'Particular') AS institucion_label
  FROM "Persona" p
  LEFT JOIN "InstituciondeSalud" i
    ON i."ID" = p."InstSalud"
),
ordenes_con_precios AS (
  SELECT
    pa.id_atencion,
    pa.fecha_base,
    pci.institucion_label,
    CASE
      WHEN pci.nombre_institucion = 'FONASA'
        THEN COALESCE(ar."ValorFonasa", 0)
      ELSE COALESCE(ar."ValorColita", 0)
    END AS precio_item
  FROM por_atencion pa
  JOIN "Orden" o   ON o."IDAtencion" = pa.id_atencion
  JOIN "Arancel" ar ON ar."ID" = o."IDArancel"
  JOIN paciente_con_institucion pci ON pci.id_paciente = pa.id_paciente
  WHERE pa.efectuada = TRUE
),
total_por_atencion AS (
  SELECT
    id_atencion,
    fecha_base,
    institucion_label,
    SUM(precio_item) AS total_atencion
  FROM ordenes_con_precios
  GROUP BY id_atencion, fecha_base, institucion_label
)
SELECT
  TO_CHAR(DATE_TRUNC('month', t.fecha_base), 'YYYY-MM') AS periodo,
  t.institucion_label AS institucion,
  COUNT(*) AS numero_atenciones,
  SUM(t.total_atencion) AS ingresos
FROM total_por_atencion t
GROUP BY 1, 2
ORDER BY 1, 2;