# Repository Guidelines

## Project Structure & Module Organization
`DumpE4.sql` captures the entire Postgres schema plus seed data used throughout Entrega 4; treat it as the canonical definition for tables such as `Agenda`, `Arancel`, and `Atencion`. `profesionconespecialidad.csv` stores supporting mappings between medical professions and their specialties and should be staged immediately after the dump is restored. Reference material lives in `enunciado e4.pdf` and `bonos, recetas y ordenes.pdf`, while `Plantilla_Informe_E3.md` remains the base template for the 2-pt report requested in the enunciado. The `firmas/` folder is a flat collection of PNG signatures keyed by beneficiary ID (for example `firmas/1023`), so scripts must read these files by ID. Reserve the repository root for PHP, SQL, HTML, CSS, and Markdown deliverables only—no backups or binary blobs beyond the provided assets.

## Administrative Obligations & Deliverables
- Timeline: etapa opens 10 Nov, closes 24 Nov 23:59 with late cutoff 27 Nov 23:59; no extensions for local machine failures, so keep a remote backup (e.g., OneDrive) as mandated.
- Support: questions go through GitHub Issues only, submitted until 20 Nov; staff replies Thursday/Saturday, only original issues get answered, and duplicates will be closed if DISCUSSIONS already cover the topic.
- Turn-in: deploy to `https://stonebraker.ing.uc.cl/<usuario_uc>.e4/E4` (files belong in `Sites/E4` on stonebraker, the official runtime so working on a personal laptop is optional) and submit just the PHP scripts, SQL, HTML, CSS, and README.md demanded by the enunciado.
- Allowed stack: stick to HTML/CSS/PHP/SQL and the techniques listed in `clases.md`; any other tool must be justified in the future README. Only first-party or in-class code is permitted, with an early-repent form available until 30 Nov for integrity breaches.

## Build, Test, and Development Commands
- `createdb e4_clinic && psql -d e4_clinic -f DumpE4.sql` initializes a clean local database using the dump; rerun whenever the schema changes upstream.
- `psql -d e4_clinic -c "\\copy staging_profesion_especialidad FROM 'profesionconespecialidad.csv' CSV HEADER ENCODING 'UTF8'"` ingests the CSV into a staging table (create it once with the expected columns before copying).
- `psql -d e4_clinic -f consultas/reporte_bonos.sql` runs any derived query pack; keep reusable queries or view definitions under `consultas/` so reviewers can replay them verbatim.
- Local Postgres setup: Postgres.app ships PostgreSQL 17 listening on `localhost:5432` with user `santiago`, empty password, and the working database `entrega_numero_4_srde`. Use `psql -h localhost -p 5432 -U santiago entrega_numero_4_srde` to reach the same environment PHP scripts will target.

## Functional Baseline from Enunciado
- Objetivo: entregar una aplicación PHP simple que opere la clínica DCColita usando índices, transacciones, vistas, stored procedures y triggers para agendas, bonos y recetarios.
- Configuración BD (14 pts): (a) índices secundarios sobre todos los campos `RUN` y uso obligatorio en consultas; (b) índice primario sobre la PK de `Agenda` (`IDMedico`, día, hora); (c) transacciones envolviendo todo ingreso a la BD; (d) stored procedure que genere los archivos de recetas y órdenes respetando textos fijos (por ejemplo “Receta Médica Electrónica”) y variables como nombre y firma del médico; (e) trigger que ejecute ese SP al cerrar una atención (diagnóstico + recetas + órdenes); (f) vista `Ficha` ordenada de la atención más reciente a la más antigua con fecha, médico, especialidad y diagnóstico; (g) validaciones de formato/dominio en cada formulario para prevenir datos mal formateados y SQL injection.
- Carpeta `Encargos (14pts)`: agrega un directorio homónimo donde cada subinciso (a–g) tendrá su script SQL dedicado (índices por RUN, índice compuesto de `Agenda`, transacciones, SP de recetas/órdenes, trigger, vista `Ficha` y validaciones). Esa carpeta sirve como bitácora de configuraciones creadas directamente en la base (sql/psql) y se describe también en el README para enlazar la implementación con la documentación.
- Diccionario de tablas: usa `tablas.md` para documentar cada entidad del dump (campos, PK/FK, uso). Este archivo es la referencia canónica para entender cómo se relacionan `Persona`, `Agenda`, `Atencion`, etc., y debe actualizarse si el esquema cambia para que los agentes sepan dónde aplicar nuevos índices o validaciones.

## Application Flow Requirements
- Manejo de usuarios (2 pts): formulario de login que autentica administradores y médicos por `ID` y `RUN` sin dígito verificador como contraseña; muestra menú administrativo o formulario médico según rol y despliega error de autenticación cuando corresponda.
- Menú Administrativo:  
  1. **Agendamiento (4 pts)** – solicita `RUN`, muestra la ficha completa (tablas `Persona`, `Rol`, `Beneficiario`), permite elegir médico por nombre o especialidad, y registra la hora en `Atencion` marcando `efectuado=false`. Si la persona existe sin rol paciente, se agrega a `Rol`; si no existe, se crea con todos los datos antes de agendar.  
  2. **Atención médica (2 pts)** – toma el `RUN`, muestra la hora próxima (ordenada por fecha/hora) y los datos del médico, emite el bono y marca la atención como efectuada dentro de una sola transacción. El bono debe seguir las reglas previas: ISAPRE (valor atención, bonificación, total), FONASA (valor arancel FONASA), particular (valor arancel clínica).  
  3. **Cancelar atención (2 pts)** – recibe el `RUN`, permite elegir entre atenciones agendadas, elimina el registro y devuelve la hora a `Agenda`.
- Formulario Médico (4 pts): muestra la `Ficha` del paciente seleccionado por `RUN`, fija la última atención efectuada por ese médico, captura el nuevo diagnóstico, medicamentos (considerando reglas de fármacos normales y psicotrópicos) y exámenes/procedimientos por nombre, inserta todo en la BD y genera las recetas/órdenes (invocando el SP/trigger).

## Coding Style & Naming Conventions
Favor uppercase SQL keywords, quoted CamelCase identifiers only when reusing the exact names shipped in `DumpE4.sql`, and introduce new columns or helper tables using `snake_case` to keep queries readable. Align multi-line `SELECT` lists and `JOIN` clauses, and wrap business logic that mutates multiple tables inside explicit `BEGIN; ... COMMIT;` transactions. Run `pg_format` on the script you edit (4-space indentation, keywords uppercase) before committing to guarantee consistent diffs.

## Scope & Allowed Techniques
Limítate a los contenidos cubiertos en `clases.md`; cualquier función, extensión o patrón no visto en cátedra debe evitarse o, si es imprescindible, documentarse explícitamente en el README pendiente con la motivación y referencias. Antes de proponer una solución verifica que la herramienta esté listada en `clases.md` y, si no lo está, crea primero la explicación para que el revisor pueda seguirla sin materiales externos.

## Testing Guidelines
Create a `tests/` folder when needed and capture every analytical query there as `.sql` fixtures that include the expected row count in a trailing comment. Run `psql -d e4_clinic -f tests/smoke.sql` before each pull request; extend the suite so every stored procedure or reporting view has at least one regression check plus an `EXPLAIN (ANALYZE, BUFFERS)` plan saved under `tests/plans/`. Aim for coverage of each business question listed in `enunciado e4.pdf`, and document assumptions inside `Plantilla_Informe_E3.md` so graders can replay your scenario.

## Reporting & Documentation
The informe (2 pts) must describe the implemented solution, cite every external resource (manuales, libros, videos), and document any execution instructions beyond the public URL—reuse `Plantilla_Informe_E3.md` and link back to the README once created. Use the README to justify herramientas fuera de clase, explain SP/trigger usage, and map each feature to su puntaje en el enunciado.

## Commit & Pull Request Guidelines
Follow Conventional Commit prefixes (`feat`, `fix`, `chore`, `docs`) to keep history grepable, for example `feat: add bonos coverage query`. Each pull request should summarize schema or data-touching changes, link to the relevant section of `enunciado e4.pdf`, list the exact `psql` commands executed, and include screenshots or text dumps when you touch anything under `firmas/`. Add a checklist covering migrations executed, tests run, and any data obfuscation performed.

## Data Handling & Security
The PNGs under `firmas/` and the PDFs in the root include sensitive personal data; keep them out of screenshots and redact IDs before sharing outside the course. Never upload unencrypted dumps derived from production-like data, and prefer environment variables for credentials instead of hard-coding them into scripts. Remove temporary exports from `/tmp` or local Downloads folders once they are loaded to avoid leaks, and remember that corrections will hit the stonebraker URL directly, so credentials and backups must stay under institutional control.
