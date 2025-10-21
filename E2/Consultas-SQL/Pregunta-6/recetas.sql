\set att_id 14
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
meds AS (
  SELECT m."Medicamento" AS medicamento,
         COALESCE(NULLIF(trim(m."Posologia"), ''), '') AS posologia,
         m."Psicotropico" AS psicotropico
  FROM medicamentos m
  JOIN att a ON a."ID" = m."IDAtencion"
),
flags AS (
  SELECT BOOL_OR(psicotropico) AS tiene_psicotropicos
  FROM meds
),
titulo AS (
  SELECT CASE WHEN f.tiene_psicotropicos
              THEN 'Receta Médica Electrónica Psicotrópicos'
              ELSE 'Receta Médica Electrónica'
         END AS titulo
  FROM flags f
),
lineas AS (
  SELECT
    string_agg(
      trim(
        CASE
          WHEN m.posologia <> '' THEN m.medicamento || ' ' || m.posologia
          ELSE m.medicamento
        END
      ),
      E'\n' ORDER BY m.medicamento
    ) AS cuerpo
  FROM meds m
)
SELECT
  (SELECT titulo FROM titulo) || E'\n' ||
  format('Paciente: %s %s', p."Nombres", p."Apellidos") || E'\n' ||
  format('RUN: %s', p."RUN") || E'\n' ||
  'Edad: ' || '' || E'\n' ||
  format('Diagnóstico: %s', a."Diagnostico") || E'\n' ||
  COALESCE((SELECT cuerpo FROM lineas), '') || E'\n' ||
  format('Fecha: %s', to_char(a.fecha, 'DD/MM/YYYY')) || E'\n' ||
  format('Médico: %s %s', md."Nombres", md."Apellidos") || E'\n' ||
  format('RUN Médico: %s', md."RUN")
FROM att a
JOIN pac p ON TRUE
JOIN med md ON TRUE;