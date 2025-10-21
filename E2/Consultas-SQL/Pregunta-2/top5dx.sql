SELECT
  p."ID"  AS id_paciente,
  p."RUN",
  COUNT(DISTINCT a."Diagnostico") AS cantidad_dx
FROM "Atencion" a
JOIN "Persona"  p ON p."ID" = a."IDPaciente"
WHERE a."Diagnostico" IS NOT NULL
GROUP BY p."ID", p."RUN"
ORDER BY cantidad_dx DESC
FETCH FIRST 5 ROWS WITH TIES;

