# Codex – Bitácora Operativa

> Referencia rápida con todo el contexto necesario para retomar el trabajo si la sesión se interrumpe. Mantener este archivo sincronizado tras cada bloque de cambios importantes.

---

## 1. Contexto General
- **Curso / Proyecto:** IIC2413 – DC Colita de Rana, Entrega 3 (depuración + carga).
- **Repo local:** `/Users/santiago/Documentos/Bases de Datos/Entregas`.
- **Repo remoto:** `origin = https://github.com/srdize3322/Proyecto-Bases-Datos.git`.
- **Rama activa:** `main`.
- **Convenciones de trabajo:**
  - Scripts PHP en `E3/RequestPHP/`.
  - CSV limpios en `E3/Depurado/`, descartes en `E3/Eliminado/`, trazas en `E3/Logs/`.
  - README general de la entrega en `E3/README.md`.
  - Commits atómicos con mensaje en minúsculas, descriptivos.

---

## 2. Cronología Detallada (Sesión Actual)

| Orden | Fecha (aprox) | Acción | Archivos principales | Commit |
|-------|---------------|--------|----------------------|--------|
| 1 | 2025-10-23 | Ajustes de `filtroFarmacia.php` (estado/canasta `activo/inactivo`) + documentación y regeneración de CSV | `E3/RequestPHP/filtroFarmacia.php`, `E3/Depurado/Farmacia_OK.csv`, `E3/Logs/Farmacia_LOG.txt`, `E3/README.md` | `actualizacion farmacia` |
| 2 | 2025-10-23 | Nuevo script `filtroArancel_DCColita.php`, generación de `_OK/_ERR/_LOG`, README | `E3/RequestPHP/filtroArancel_DCColita.php`, `E3/Depurado/Arancel_DCColita_OK.csv`, `E3/Eliminado/Arancel_DCColita_ERR.csv`, `E3/Logs/Arancel_DCColita_LOG.txt`, `E3/README.md` | `arancel dcc colita de rana` |
| 3 | 2025-10-23 | Corrección BOM en encabezados de Arancel DCColita | archivos anteriores | `ajuste encabezado arancel dcc` |
| 4 | 2025-10-23 | Nuevo `filtroArancelFonasa.php`, generación de salidas y README | `E3/RequestPHP/filtroArancelFonasa.php`, `E3/Depurado/Arancel_Fonasa_OK.csv`, `E3/Eliminado/Arancel_Fonasa_ERR.csv`, `E3/Logs/Arancel_Fonasa_LOG.txt`, `E3/README.md` | `arancel fonasa` |
| 5 | 2025-10-23 | Implementación `filtroAtencion.php` + salidas y README | `E3/RequestPHP/filtroAtencion.php`, `E3/Depurado/Atencion_OK.csv`, `E3/Eliminado/Atencion_ERR.csv`, `E3/Logs/Atencion_LOG.txt`, `E3/README.md` | `atencion` |
| 6 | 2025-10-23 | Creación y commit de esta bitácora | `E3/Codex.md` | `bitacora codex` / `bitacora codex v2` |
| 7 | 2025-10-24 | Implementación `filtroMedicamento.php` + salidas y README | `E3/RequestPHP/filtroMedicamento.php`, `E3/Depurado/Medicamento_OK.csv`, `E3/Eliminado/Medicamento_ERR.csv`, `E3/Logs/Medicamento_LOG.txt`, `E3/README.md` | `medicamento` |
| 8 | 2025-10-24 | Implementación `filtroOrden.php` + salidas y README | `E3/RequestPHP/filtroOrden.php`, `E3/Depurado/Orden_OK.csv`, `E3/Eliminado/Orden_ERR.csv`, `E3/Logs/Orden_LOG.txt`, `E3/README.md` | `orden` |
| 9 | 2025-10-24 | Implementación `filtroPlanes.php` + salidas y README | `E3/RequestPHP/filtroPlanes.php`, `E3/Depurado/planes/*_OK.csv`, `E3/Eliminado/planes/*_ERR.csv`, `E3/Logs/planes/*_LOG.txt`, `E3/README.md` | `planes` |
| 10 | 2025-10-24 | Agregado `main.php` (orquestador de filtros) | `E3/main.php`, README/Codex | _pendiente de commit_ |
| 11 | 2025-10-29 | `filtroPersona.php`: elimina BOM, valida titular de beneficiarios, normaliza profesiones; regeneración de CSV y log | `E3/RequestPHP/filtroPersona.php`, `E3/Depurado/Persona_OK.csv`, `E3/Eliminado/Persona_ERR.csv`, `E3/Logs/Persona_LOG.txt` | `ajuste filtro persona` |
| 12 | 2025-10-29 | `filtroInstituciones.php`: limpia encabezado, normaliza tipo/enlace, evita warnings PHP | `E3/RequestPHP/filtroInstituciones.php`, `E3/Depurado/Instituciones previsionales de salud_OK.csv`, `E3/Eliminado/Instituciones previsionales de salud_ERR.csv` | `ajuste instituciones` |
| 13 | 2025-10-29 | `filtroFarmacia.php`: canasta {0,1}, catálogo de tipos en título; regeneración de CSV y log | `E3/RequestPHP/filtroFarmacia.php`, `E3/Depurado/Farmacia_OK.csv`, `E3/Logs/Farmacia_LOG.txt`, `E3/Codex.md` | `ajuste farmacia` |
| 14 | 2025-10-29 | `filtroArancel_DCColita.php`: normaliza encabezado (sin espacios), regeneración de CSV/log | `E3/RequestPHP/filtroArancel_DCColita.php`, `E3/Depurado/Arancel_DCColita_OK.csv`, `E3/Logs/Arancel_DCColita_LOG.txt` | `ajuste arancel dcc` |
| 15 | 2025-10-29 | `filtroArancelFonasa.php`: encabezados saneados, regeneración de CSV/log | `E3/RequestPHP/filtroArancelFonasa.php`, `E3/Depurado/Arancel_Fonasa_OK.csv`, `E3/Logs/Arancel_Fonasa_LOG.txt` | _pendiente de commit_ |

> **Estado actual:** Todo lo anterior está en `origin/main`. La bitácora actual todavía no se ha commiteado (ver Sección 6).

---

## 3. Scripts Disponibles (E3/RequestPHP/)

| Script | Dataset | Salidas generadas | Notas clave |
|--------|---------|-------------------|-------------|
| `filtroPersona.php` | `Old/Persona.csv` | `Persona_OK/ERR/LOG` | Normaliza RUN, correos, teléfonos, roles e instituciones (FONASA por defecto); elimina BOM; descarta beneficiarios sin titular válido y canoniza profesiones clínicas (Tens→Tens, Médico, etc.). |
| `filtroInstituciones.php` | `Old/Instituciones previsionales de salud.csv` | `Instituciones previsionales de salud_OK/ERR/LOG` | Limpia nombres, enlaces (sin protocolo), valida RUT (módulo 11); cabecera sin BOM. |
| `filtroFarmacia.php` | `Old/Farmacia.csv` | `Farmacia_OK/ERR/LOG` | Códigos únicos, normaliza campos; `tipo` en catálogo ({Fármacos, Insumos,…}); estado `activo/inactivo`; canasta → `{0,1}`. |
| `filtroArancel_DCColita.php` | `Old/Arancel DCColita de rana.csv` | `Arancel_DCColita_OK/ERR/LOG` | `codigo` único, `codFonasa` patrón `entero`/`entero-entero`, encabezados sin espacios extra, atenciones truncadas a 100, valor entero. |
| `filtroArancelFonasa.php` | `Old/Arancel fonasa.csv` | `Arancel_Fonasa_OK/ERR/LOG` | `codF` único sin ceros a la izquierda, `codA` opcional, encabezados normalizados, `grupo`/`tipo` ≤30 chars, valor entero. |
| `filtroAtencion.php` | `Old/Atencion.csv` | `Atencion_OK/ERR/LOG` | ID único, fecha ISO, RUN validados contra personas, diagnóstico limpio (mojibake), `efectuada` consistente. |
| `filtroMedicamento.php` | `Old/Medicamento.csv` | `Medicamento_OK/ERR/LOG` | ID de atención limpiado a entero, textos ≤100 caracteres, boolean normalizado, posología por defecto cuando falta. |
| `filtroOrden.php` | `Old/Orden.csv` | `Orden_OK/ERR/LOG` | IDs numéricos limpios, descripciones normalizadas/truncadas, columnas extra removidas. |
| `filtroPlanes.php` | `Old/planes/*.csv` | `Depurado/planes/*_OK`, `Eliminado/planes/*_ERR`, `Logs/planes/*_LOG` | Bonificación (0–100) y grupos normalizados por plan, se recorren todos los archivos de la carpeta. |
| `main.php` | Orquestador | — | Ejecuta en cadena todos los `filtro*.php` para regenerar las carpetas Depurado/Eliminado/Logs. |

> Scripts faltantes: Validaciones cruzadas, utilidades comunes, `main.php`, `validador.php`.

---

## 4. CSV Resultantes (caras visibles)

| CSV depurado | Observaciones |
|--------------|---------------|
| `Persona_OK.csv` | Cabecera sin BOM; teléfonos normalizados a 9 dígitos (con fallback 111111111); instituciones validadas contra catálogo; beneficiarios sin titular se van a `_ERR`; profesiones clínicas canonizadas. |
| `Instituciones previsionales de salud_OK.csv` | Cabecera sin BOM; RUT normalizados; enlaces normalizados (sin protocolo, minúsculas). |
| `Farmacia_OK.csv` | Encabezados entre comillas por espacios; códigos únicos; `tipo` en catálogo; estado `activo/inactivo`; canasta `{0,1}`; log documenta duplicados y ajustes. |
| `Arancel_DCColita_OK.csv` | Sin BOM; encabezados saneados (sin espacios extra); descripciones truncadas (log indica líneas afectadas); duplicados van a `_ERR`. |
| `Arancel_Fonasa_OK.csv` | Encabezado sin BOM ni espacios extra; valores sin puntos; grupos/tipos truncados si exceden 30 caracteres; `_ERR` solo con encabezado. |
| `Atencion_OK.csv` | Fechas en formato ISO, RUN validados contra personas, diagnósticos depurados (sin mojibake) y vacíos cuando la atención no se efectuó. |
| `Medicamento_OK.csv` | IDs válidos según atenciones, nombres y posologías normalizados (máx. 100 caracteres), marcador psicotrópico en dominio `TRUE/FALSE`. |
| `Orden_OK.csv` | Identificadores numéricos listos para usar como FK y descripciones sanitizadas (truncadas a 100) sin columnas residuales. |
| `Depurado/planes/*_OK.csv` | Bonificaciones enteras (0–100) por plan e isapre, grupos normalizados sin mojibake ni filas en blanco. |

---

## 5. Pending / To-Do

1. **Scripts pendientes:** Validaciones cruzadas (FOREIGN KEY), utilidades comunes, `main.php`, `validador.php`.
2. **BOM residual:** Revisar si quedan cabeceras con BOM en otros CSV (p.ej. `Instituciones...` ERR, logs heredados) y sanitizarlas al ajustar cada filtro.
3. **Referencias cruzadas:** Validar:
   - RUN en `Atencion` y `Orden` contra `Persona_OK`.
   - Códigos de aranceles (Fonasa/DC) y medicamentos contra sus catálogos depurados.
4. **Documentación:** Continuar ampliando `E3/README.md` con cada nuevo script + decisiones de negocio.
5. **Carga a BD:** Redactar `carga.sql` con DDL + COPY/ \copy y manejo de errores (`cargaERR.csv`, `cargaLOG.txt`).
6. **Probar pipeline completo:** Al implementar `main.php`, automatizar ejecución ordenada y revisar logs finales.
7. **Ignorar basura del sistema:** Agregar `.DS_Store` al `.gitignore` para evitar ruido en futuros commits.

---

## 6. Estado Git Actual

```
git status --short
 M .DS_Store
 M E3/.DS_Store
?? E3/Old/.DS_Store
?? E3/RequestPHP/filtroAtencion.php   ← nuevo script
?? E3/Depurado/Atencion_OK.csv
?? E3/Eliminado/Atencion_ERR.csv
?? E3/Logs/Atencion_LOG.txt
?? E3/RequestPHP/filtroMedicamento.php   ← nuevo script
?? E3/Depurado/Medicamento_OK.csv
?? E3/Eliminado/Medicamento_ERR.csv
?? E3/Logs/Medicamento_LOG.txt
?? E3/RequestPHP/filtroPlanes.php   ← nuevo script
?? E3/Depurado/planes
?? E3/Eliminado/planes
?? E3/Logs/planes
?? E3/RequestPHP/filtroOrden.php   ← nuevo script
?? E3/Depurado/Orden_OK.csv
?? E3/Eliminado/Orden_ERR.csv
?? E3/Logs/Orden_LOG.txt
?? E3/main.php   ← orquestador
 M E3/README.md
 M E3/Codex.md   ← esta versión aún sin commit/push
```

> Recordatorio: Al terminar de editar esta bitácora, ejecutar:
```
git add E3/Codex.md
git commit -m "bitacora codex vX"
git push
```
> (y considerar limpiar/ignorar los `.DS_Store`).

---

## 7. Buenas Prácticas Adoptadas
- **Uso de `fputcsv`:** Consistencia en delimitador `;`, comillas `"`, escape `\\`.
- **Logs detallados:** Cada script documenta correcciones con número de línea (`Lnnn`), garantizando trazabilidad.
- **Estrategia duplicados:** Mantener primer registro válido y mover el resto a `_ERR` para revisión manual.
- **Commits frecuentes:** Cambios agrupados por feature/dataset, con mensajes descriptivos y push inmediato.
- **Backups de contexto:** Este archivo reemplaza la “memoria” del asistente cuando no está disponible.

---

## 8. Referencias Rápidas
- **Scripts ejecutados manualmente:**
  - `php E3/RequestPHP/filtroFarmacia.php`
  - `php E3/RequestPHP/filtroArancel_DCColita.php`
  - `php E3/RequestPHP/filtroArancelFonasa.php`
  - `php E3/RequestPHP/filtroAtencion.php`
  - `php E3/RequestPHP/filtroMedicamento.php`
  - `php E3/RequestPHP/filtroOrden.php`
  - `php E3/RequestPHP/filtroPlanes.php`
  - `php E3/main.php` (ejecución orquestada)
- **Consultas útiles:**
  - `head -n 5 E3/Depurado/<archivo>` para validar formato.
  - `rg "-> ERR" E3/Logs/<archivo>` para encontrar descartes críticos.
  - `xxd -l 16 <archivo>` para detectar BOM.

---

## 9. Notas Personales / Decisiones
- Cuando falta institución previsional en `Persona`, se asigna **FONASA** justificando cobertura pública.
- En farmacia, códigos duplicados se descartan por completo; no se fusionan precios ni descripciones.
- En Arancel DCColita, la existencia de múltiples filas para un mismo código se resuelve quedándose con la primera válida (fuera de nuestro mandato decidir cuál es correcta).
- Arancel Fonasa exige truncar `grupo`/`tipo` para que se ajusten a la 3FN target.

---

*Última actualización:* 2025-10-24 02:20 (actualizar manualmente tras cada edición).
