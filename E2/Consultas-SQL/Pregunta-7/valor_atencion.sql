\set att_id 144 --arbitrario

WITH
att AS (
  SELECT a."ID", a.fecha, a."Diagnostico", a."IDPaciente", a."IDMedico"
  FROM "Atencion" a
  WHERE a."ID" = :att_id
),
pac AS (
  SELECT p."RUN" AS run_paciente, p."Nombres" AS nom_pac, p."Apellidos" AS ape_pac
  FROM "Persona" p
  JOIN att a ON a."IDPaciente" = p."ID"
),
med AS (
  SELECT m."RUN" AS run_medico, m."Nombres" AS nom_med, m."Apellidos" AS ape_med
  FROM "Persona" m
  JOIN att a ON a."IDMedico" = m."ID"
),
it_arancel AS (
  SELECT
    ar."ID",
    ar."Codigo",
    ar."ConsAtMedica",
    UPPER(TRIM(COALESCE(ar."Tipo", ''))) AS tipo_norm,
    COALESCE(ar."ValorColita", 0) AS precio_lista
  FROM "Orden" o
  JOIN "Arancel" ar ON ar."ID" = o."IDArancel"
  JOIN att a ON a."ID" = o."IDAtencion"
),
clasif AS (
  SELECT ia.*,
         CASE
           WHEN ia.tipo_norm LIKE 'CONSULTAS%' OR LEFT(ia."Codigo", 2) = '01'
           THEN 'ATENCION' ELSE 'ORDEN'
         END AS clase
  FROM it_arancel ia
),
sumas_arancel AS (
  SELECT
    SUM(CASE WHEN clase = 'ATENCION' THEN precio_lista ELSE 0 END) AS valor_atencion,
    SUM(CASE WHEN clase = 'ORDEN'    THEN precio_lista ELSE 0 END) AS valor_ordenes
  FROM clasif
),
meds AS (
  SELECT m."Medicamento"
  FROM medicamentos m
  JOIN att a ON a."ID" = m."IDAtencion"
),
sumas_meds AS (
  SELECT COALESCE(SUM(f."Precio"), 0) AS valor_medicamentos
  FROM meds
  JOIN "Farmacia" f ON f."Nombre" = meds."Medicamento"
)

SELECT
  a."ID"                                                     AS id_atencion,
  to_char(a.fecha, 'DD/MM/YYYY')                             AS fecha,
  (p.nom_pac || ' ' || p.ape_pac)                            AS paciente,
  p.run_paciente,
  (m.nom_med || ' ' || m.ape_med)                            AS medico,
  m.run_medico,
  COALESCE(NULLIF(a."Diagnostico", ''), '')                  AS diagnostico,
  COALESCE(sa.valor_atencion, 0)                             AS valor_atencion,
  COALESCE(sa.valor_ordenes, 0)                              AS valor_ordenes,
  COALESCE(sm.valor_medicamentos, 0)                         AS valor_medicamentos,
  COALESCE(sa.valor_atencion, 0)
+ COALESCE(sa.valor_ordenes, 0)
+ COALESCE(sm.valor_medicamentos, 0)                         AS valor_total
FROM att a
CROSS JOIN pac p
CROSS JOIN med m
CROSS JOIN sumas_arancel sa
CROSS JOIN sumas_meds sm;