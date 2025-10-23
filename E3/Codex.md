# Codex – Bitácora de Trabajo

Este documento replica la “memoria” de la sesión para que, si la conexión se corta, se pueda reconstruir rápidamente el contexto y continuar el proyecto.

## Contexto
- **Proyecto:** DC Colita de Rana – Entrega 3 (depuración y carga de datos).
- **Ubicación de trabajo:** `/Users/santiago/Documentos/Bases de Datos/Entregas`.
- **Ramas/Repositorio:** Rama `main`, remoto `origin` (`https://github.com/srdize3322/Proyecto-Bases-Datos.git`).
- **Formato general:** Scripts PHP en `E3/RequestPHP/`, salidas depuradas en `E3/Depurado/`, descartes en `E3/Eliminado/`, logs en `E3/Logs/`.

## Historial de acciones clave

### 1. Normalización Farmacia
- Archivo procesado: `Old/Farmacia.csv`.
- Script involucrado: `RequestPHP/filtroFarmacia.php`.
- Cambios principales (commit `actualizacion farmacia`):
  - Telefónos en formato `activo/inactivo`.
  - Corrección de códigos duplicados y limpieza de campos.
  - README actualizado con explicación del flujo.
- Validado ejecutando el script manualmente.

### 2. Arancel DC Colita de Rana
- Archivo procesado: `Old/Arancel DCColita de rana.csv`.
- Script creado: `RequestPHP/filtroArancel_DCColita.php`.
- Reglas clave:
  - `codigo` numérico único (descarta duplicados, se queda con la primera aparición válida).
  - `codFonasa` patrón `entero` o `entero-entero`.
  - `atencion` truncada a 100 caracteres.
  - `valor` numérico; filas irrecuperables a `_ERR`.
- Salidas generadas: `Arancel_DCColita_OK.csv`, `Arancel_DCColita_ERR.csv`, `Arancel_DCColita_LOG.txt`.
- README documentado (`1.4.4`).
- Corrección posterior: eliminación de BOM en encabezado (commit `ajuste encabezado arancel dcc`).

### 3. Arancel Fonasa
- Archivo procesado: `Old/Arancel fonasa.csv`.
- Nuevo script: `RequestPHP/filtroArancelFonasa.php`.
- Reglas:
  - `codF` entero único (sin ceros a la izquierda).
  - `codA` opcional, solo dígitos.
  - `atencion` <=100 caracteres, `grupo` y `tipo` <=30 caracteres.
  - `valor` entero (se eliminan separadores).
- Salidas generadas: `Arancel_Fonasa_OK.csv`, `Arancel_Fonasa_ERR.csv` (solo encabezado), `Arancel_Fonasa_LOG.txt`.
- Documentado en README (`1.4.5`).

### 4. Estado del repositorio
- Commits publicados (en orden cronológico):
  1. `actualizacion farmacia`
  2. `arancel dcc colita de rana`
  3. `ajuste encabezado arancel dcc`
  4. `arancel fonasa`
- Último `git push` ejecutado correctamente (branch `main`).

## Observaciones pendientes / siguientes pasos sugeridos
1. **Scripts faltantes:** Medicamento, Persona (ya existe), Atencion, Orden, Planes, etc. Deben crear sus `_OK/_ERR/_LOG` y actualizar README.
2. **BOM**: Persisten en algunos CSV (Persona e Instituciones). Conviene replicar la limpieza aplicada en aranceles.
3. **Referencias cruzadas:** En futuras limpiezas, validar RUN vs Persona y códigos vs catálogos depurados.
4. **carga.sql** y scripts de orquestación (`main.php`, `validador.php`) aún no implementados.

## Uso del archivo
- Ante pérdida de contexto, leer esta bitácora para saber qué scripts existen, qué commits se han hecho y cuáles son los acuerdos de formato.
- Actualizar la sección de “Observaciones” con nuevos pendientes a medida que se avance.


*Última actualización:* 2025-10-23 14:40 (actualizar manualmente si el archivo se edita).
