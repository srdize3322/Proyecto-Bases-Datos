WITH conteos AS (
  SELECT
    a."IDPaciente"      AS id_paciente,
    p."RUN",
    m."Medicamento"     AS medicamento,
    COUNT(*)            AS veces,
    MAX(COUNT(*)) OVER (PARTITION BY a."IDPaciente") AS max_veces
  FROM "Atencion" a
  JOIN "Persona" p ON p."ID" = a."IDPaciente"
  JOIN medicamentos m ON m."IDAtencion" = a."ID"
  GROUP BY a."IDPaciente", p."RUN", m."Medicamento"
),
umbral AS (
  -- Obtener el valor min del top 5
  SELECT MIN(max_veces) AS valor_top5
  FROM (SELECT DISTINCT max_veces FROM conteos ORDER BY max_veces DESC LIMIT 5) t
)
SELECT
  id_paciente,
  "RUN",
  medicamento,
  veces AS veces_recetado
FROM conteos, umbral
WHERE veces = max_veces
  AND max_veces >= valor_top5
ORDER BY max_veces DESC, id_paciente, medicamento;