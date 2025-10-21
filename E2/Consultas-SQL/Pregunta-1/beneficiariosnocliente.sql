WITH pacientes AS (
  SELECT DISTINCT "IDPersona" AS id
  FROM "Rol"
  WHERE lower(trim("Rol")) = 'paciente'
),
titulares_pacientes AS (
  SELECT DISTINCT b."IDpersona" AS id_titular
  FROM beneficiario b
  JOIN pacientes p ON p.id = b."IDpersona"
  WHERE b."Beneficiario" = false
)
SELECT DISTINCT
  t."ID"        AS id_titular,
  t."RUN",
  t."Nombres",
  t."Apellidos",
  i."Nombre"    AS institucion
FROM titulares_pacientes tp
JOIN "Persona" t             ON t."ID" = tp.id_titular
JOIN "InstituciondeSalud" i  ON i."ID" = t."InstSalud"
WHERE EXISTS (
  SELECT 1
  FROM beneficiario b
  LEFT JOIN pacientes p_b ON p_b.id = b."IDpersona"
  WHERE b."IDtitular" = t."ID"
    AND b."Beneficiario" = true
    AND p_b.id IS NULL  
)
ORDER BY i."Nombre", t."Apellidos", t."Nombres";