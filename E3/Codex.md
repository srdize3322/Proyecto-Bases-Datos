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
| 5 | 2025-10-23 | Creación y commit de esta bitácora | `E3/Codex.md` | `bitacora codex` (reemplazado por versión actual) |

> **Estado actual:** Todo lo anterior está en `origin/main`. La bitácora actual todavía no se ha commiteado (ver Sección 6).

---

## 3. Scripts Disponibles (E3/RequestPHP/)

| Script | Dataset | Salidas generadas | Notas clave |
|--------|---------|-------------------|-------------|
| `filtroPersona.php` | `Old/Persona.csv` | `Persona_OK/ERR/LOG` | Normaliza RUN, correos, teléfonos, roles, instituciones (FONASA por defecto). Aún mantiene BOM en encabezado. |
| `filtroInstituciones.php` | `Old/Instituciones previsionales de salud.csv` | `Instituciones previsionales de salud_OK/ERR/LOG` | Limpia nombres, enlaces, valida RUT (módulo 11). Hay BOM en CSV generado. |
| `filtroFarmacia.php` | `Old/Farmacia.csv` | `Farmacia_OK/ERR/LOG` | Códigos únicos, normaliza campos, estado y canasta → `activo/inactivo`. |
| `filtroArancel_DCColita.php` | `Old/Arancel DCColita de rana.csv` | `Arancel_DCColita_OK/ERR/LOG` | `codigo` único, `codFonasa` patrón `entero`/`entero-entero`, truncado atenciones 100 caracteres, valor entero. |
| `filtroArancelFonasa.php` | `Old/Arancel fonasa.csv` | `Arancel_Fonasa_OK/ERR/LOG` | `codF` único sin ceros a la izquierda, `codA` opcional, `grupo`/`tipo` ≤30 chars, valor entero. |

> Scripts faltantes: medicamentos, atenciones, órdenes, planes, validaciones cruzadas, `main.php`, `validador.php`.

---

## 4. CSV Resultantes (caras visibles)

| CSV depurado | Observaciones |
|--------------|---------------|
| `Persona_OK.csv` | Cabecera con BOM; campos con comillas según necesidad; teléfono 9 dígitos; instituciones apoyadas en catálogo. |
| `Instituciones previsionales de salud_OK.csv` | Cabecera con BOM; RUT normalizados; enlaces sin protocolo. |
| `Farmacia_OK.csv` | Encabezados entre comillas por espacios; códigos únicos; estado/canasta `activo/inactivo`; log documenta duplicados removidos. |
| `Arancel_DCColita_OK.csv` | Sin BOM; descripciones truncadas (log indica líneas afectadas); duplicados van a `_ERR`. |
| `Arancel_Fonasa_OK.csv` | Encabezado sin BOM; valores sin puntos; grupos/tipos truncados si exceden 30 caracteres; `_ERR` solo con encabezado. |

---

## 5. Pending / To-Do

1. **Scripts pendientes:** Medicamento, Atencion, Orden, Planes, utilidades comunes, `main.php`, `validador.php`.
2. **BOM residual:** Eliminar BOM en `Persona_OK.csv` y `Instituciones..._OK.csv` replicando la lógica usada en aranceles.
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
 M E3/README.md
?? E3/Codex.md   ← esta versión aún sin commit/push
?? E3/Old/.DS_Store
```

> Recordatorio: Al terminar de editar esta bitácora, ejecutar:
```
git add E3/Codex.md
git commit -m "bitacora codex v2"
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

*Última actualización:* 2025-10-23 15:05 (actualizar manualmente tras cada edición).
