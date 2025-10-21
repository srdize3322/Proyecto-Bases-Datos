\set att_id 144

\pset format unaligned
\pset tuples_only on

WITH att AS (
  SELECT a."ID", a.fecha, a."Diagnostico", a."IDPaciente", a."IDMedico"
  FROM "Atencion" a
  WHERE a."ID" = :att_id
),
pac AS (
  SELECT p."ID", p."RUN", p."Nombres", p."Apellidos"
  FROM "Persona" p
  JOIN att a ON a."IDPaciente" = p."ID"
),
med AS (
  SELECT m."ID", m."RUN", m."Nombres", m."Apellidos"
  FROM "Persona" m
  JOIN att a ON a."IDMedico" = m."ID"
),
examenes AS (
  SELECT DISTINCT
         trim(ar."ConsAtMedica") AS examen
  FROM "Orden" o
  JOIN "Arancel" ar ON ar."ID" = o."IDArancel"
  JOIN att a        ON a."ID" = o."IDAtencion"
  WHERE
        LEFT(ar."Codigo", 2) IN ('03','04')
     OR 
        lower(ar."ConsAtMedica") ~ '(examen|perfil|radiograf|ecograf|resonancia|tomograf|imagen|cultivo|test|prueba)'
),
lineas AS (
  SELECT NULLIF(
           string_agg(examen, E'\n' ORDER BY examen),
           ''
         ) AS cuerpo
  FROM examenes
)
SELECT
  'Orden de Examen' || E'\n' ||
  format('Paciente: %s %s', p."Nombres", p."Apellidos") || E'\n' ||
  format('RUN: %s', p."RUN") || E'\n' ||
  'Edad: ' || '' || E'\n' ||
  COALESCE(NULLIF(a."Diagnostico", ''), '') ||
  CASE WHEN a."Diagnostico" IS NULL OR a."Diagnostico" = '' THEN '' ELSE E'\n' END ||
  COALESCE((SELECT cuerpo FROM lineas), '(Sin órdenes de examen)') || E'\n' ||
  format('Fecha: %s', to_char(a.fecha, 'DD/MM/YYYY')) || E'\n' ||
  format('Médico: %s %s', md."Nombres", md."Apellidos") || E'\n' ||
  format('RUN Médico: %s', md."RUN")
FROM att a
JOIN pac p ON TRUE
JOIN med md ON TRUE;