-- Mensual total Prueba
WITH por_atencion AS (
  SELECT
    a."ID"                 AS id_atencion,
    BOOL_OR(a."Efectuada") AS realizada,
    MIN(a.fecha)           AS fecha_base
  FROM "Atencion" a
  GROUP BY a."ID"
),
mensual_total AS (
  SELECT
    TO_CHAR(DATE_TRUNC('month', fecha_base), 'YYYY-MM') AS periodo,
    COUNT(*) AS atenciones_no_efectuadas
  FROM por_atencion
  WHERE realizada = false
  GROUP BY 1
)
SELECT 'mensual_total' AS seccion, periodo, atenciones_no_efectuadas
FROM mensual_total
ORDER BY periodo;

--  Top 5 docs por promedio mes
WITH por_atencion AS (
  SELECT
    a."ID"                 AS id_atencion,
    BOOL_OR(a."Efectuada") AS realizada,
    MIN(a.fecha)           AS fecha_base,
    MAX(a."IDMedico")      AS id_medico
  FROM "Atencion" a
  GROUP BY a."ID"
),
medico_mensual AS (
  SELECT
    pa.id_medico,
    DATE_TRUNC('month', pa.fecha_base) AS mes,
    COUNT(*) AS perdidas_mes
  FROM por_atencion pa
  WHERE pa.realizada = false
  GROUP BY pa.id_medico, DATE_TRUNC('month', pa.fecha_base)
),
medico_promedio AS (
  SELECT
    mm.id_medico,
    AVG(mm.perdidas_mes::numeric) AS avg_per_mes
  FROM medico_mensual mm
  GROUP BY mm.id_medico
),
medico_ranked AS (
  SELECT
    p."ID" AS id_persona, p."RUN", p."Nombres", p."Apellidos",
    mp.avg_per_mes,
    RANK() OVER (ORDER BY mp.avg_per_mes DESC, p."ID") AS rk
  FROM medico_promedio mp
  JOIN "Persona" p ON p."ID" = mp.id_medico
)
SELECT 'top_medicos_promedio' AS seccion,
       id_persona, "RUN", "Nombres", "Apellidos",
       ROUND(avg_per_mes, 2) AS avg_per_mes
FROM medico_ranked
WHERE rk <= 5
ORDER BY avg_per_mes DESC, id_persona;


-- Top pacientes promedio x mes
WITH por_atencion AS (
  SELECT
    a."ID"                 AS id_atencion,
    BOOL_OR(a."Efectuada") AS realizada,
    MIN(a.fecha)           AS fecha_base,
    MAX(a."IDPaciente")    AS id_paciente
  FROM "Atencion" a
  GROUP BY a."ID"
),
paciente_mensual AS (
  SELECT
    pa.id_paciente,
    DATE_TRUNC('month', pa.fecha_base) AS mes,
    COUNT(*) AS perdidas_mes
  FROM por_atencion pa
  WHERE pa.realizada = false
  GROUP BY pa.id_paciente, DATE_TRUNC('month', pa.fecha_base)
),
paciente_promedio AS (
  SELECT
    pm.id_paciente,
    AVG(pm.perdidas_mes::numeric) AS avg_per_mes
  FROM paciente_mensual pm
  GROUP BY pm.id_paciente
),
paciente_ranked AS (
  SELECT
    p."ID" AS id_persona, p."RUN", p."Nombres", p."Apellidos",
    pp.avg_per_mes,
    RANK() OVER (ORDER BY pp.avg_per_mes DESC, p."ID") AS rk
  FROM paciente_promedio pp
  JOIN "Persona" p ON p."ID" = pp.id_paciente
)
SELECT 'top_pacientes_promedio' AS seccion,
       id_persona, "RUN", "Nombres", "Apellidos",
       ROUND(avg_per_mes, 2) AS avg_per_mes
FROM paciente_ranked
WHERE rk <= 5
ORDER BY avg_per_mes DESC, id_persona;