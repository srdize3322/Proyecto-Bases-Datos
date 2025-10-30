## Guía para Agente SQL – Entrega 3 (DC Colita de Rana)

### 1. Puntos de partida
- **Repositorio local**: `/Users/santiago/Documentos/Bases de Datos/Entregas/E3`.
- **Estructura clave**:
  - `Old/`: CSV originales (ya no se tocan).
  - `Depurado/`: CSV limpios que usaremos para cargar la BD.
  - `Eliminado/`: registros irrecuperables (referencia histórica).
  - `Logs/`: trazabilidad de cada filtro PHP.
  - `RequestPHP/`: scripts de limpieza (ya ejecutados).
  - `RequestSQL/`: **tu espacio de trabajo** para consultas y scripts SQL.
  - `Codex.md`: bitácora operativa (qué se ha hecho, qué falta).
  - `Instrucciones.md`: prompt general con supuestos y checklist.
  - `Old/Enunciado E3.txt`: extracto del enunciado oficial (ver secciones de modelo y requisitos de carga).

### 2. Flujo recomendado
1. **Familiarízate con la bitácora** (`Codex.md`) para entender estado actual y decisiones previas.
2. **Lee la propuesta de esquema** (`EstructuraInicial.md` + `EstructuraE2.md`) y el extracto del enunciado (`Old/Enunciado E3.txt`), especialmente las secciones:
   - Arancel DCColita / Fonasa.
   - Restricciones FK de Atención, Orden, Medicamento, Planes.
3. **Valida los CSV depurados** en `Depurado/` según tu necesidad. Todos están codificados en UTF-8 y listos para `COPY`.
4. **Trabaja en `RequestSQL/`**:
   - Usa archivos `.sql` numerados o con propósito claro (ej. `01_schema.sql`, `02_copy.sql`, `check_integridad.sql`).
   - Guarda también consultas de verificación (conteos, joins de sanity check).
   - Si necesitas scripts auxiliares (por ejemplo, limpieza adicional vía SQL), mantenlos en este directorio.
5. **Git workflow**:
   - Antes de empezar, sincroniza (`git pull`).
   - Tras cada bloque consistente (ej. creación de esquema, carga + pruebas), `git add`, `git commit -m "mensaje claro"` y `git push`.
   - El remoto está configurado en `origin = https://github.com/srdize3322/Proyecto-Bases-Datos.git`.
6. **Pruebas**:
   - Provee consultas que demuestren integridad (FK, conteos, ejemplos).
   - Documenta en `RequestSQL/README` si creas procesos no triviales (opcional).

### 3. Lógica general depurada (referencia rápida)
- **Personas / Instituciones / Farmacia / Aranceles / Atención / Medicamento / Orden / Planes** ya fueron limpiados por PHP:
  - RUN validados, correos/teléfonos normalizados, dominios de campos controlados.
  - FK aseguradas en las salidas (`Orden_OK` y `Medicamento_OK` enlazan con `Atencion_OK` y `Arancel_DCColita_OK`).
  - Planes: bonificación 0–100 y grupos saneados.
  - Logs detallan cada corrección; si necesitas justificar decisiones, consulta el archivo correspondiente en `Logs/`.

### 4. Consideraciones SQL
- **Crea primero el esquema** (DDL completo en el archivo principal de carga).
- **COPY**: usa `FORMAT csv`, `HEADER`, `DELIMITER ';'`. Ejemplo:
  ```sql
  COPY persona FROM '/ruta/Depurado/Persona_OK.csv'
    WITH (FORMAT csv, HEADER, DELIMITER ';');
  ```
  Ajusta rutas absolutas según el ambiente en que ejecutes (`psql` local o server).
- **Manejo de errores**: si planificas generar `cargaERR.csv` / `cargaLOG.txt`, define desde el inicio cómo capturar filas problemáticas.
- **Consultas de verificación**: guarda tus sentencias en `RequestSQL/` (por ejemplo, `checks.sql`) y referencia en el README final.

### 5. Comunicación / Documentación
- Si cambias supuestos o detectas inconsistencias, actualiza `Codex.md` y, si aplica, `README.md`.
- Mantén los commits atómicos y descriptivos (minúsculas, acción + contexto).
- Cualquier SQL ad-hoc debe quedar versionado en `RequestSQL/`.

Con esto deberías estar listo para levantar la BD, ejecutar tus cargas y documentar las verificaciones. ¡Éxito! 💪
