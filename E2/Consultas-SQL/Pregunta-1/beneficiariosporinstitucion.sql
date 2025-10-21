WITH pacientes AS (
  SELECT DISTINCT "IDPersona" AS id
  FROM "Rol"
  WHERE lower(trim("Rol")) = 'paciente'
)
SELECT
  i."Nombre" AS institucion,
  COUNT(DISTINCT per."ID") FILTER (WHERE b."Beneficiario" = false) AS titulares_pacientes,
  COUNT(DISTINCT per."ID") FILTER (WHERE b."Beneficiario" = true)  AS no_titulares_pacientes
FROM beneficiario b
JOIN "Persona" per           ON per."ID" = b."IDpersona"
JOIN pacientes p             ON p.id     = per."ID"
JOIN "InstituciondeSalud" i  ON i."ID"   = per."InstSalud"
GROUP BY i."Nombre"
ORDER BY i."Nombre";