# Entrega 3 – Depuración de Datos  
**Proyecto:** *DC Colita de Rana*  
**Curso:** IIC2413 – Bases de Datos 2025-2  
**Profesor:** Eduardo Bustos  
**Ayudante:** Martina Molina  
**Alumno:** Santiago Díaz Edwards


---
## Planteamiento de la Estructura de la Base de Datos previo a filtrado

![Diagrama Entidad-Relación](ImagenesERD/E3.png)
![Diagrama Entidad-Relación-1](ImagenesERD/E3L.png)

| Tabla | Atributos principales | Claves y restricciones | Relaciones relevantes |
|-------|----------------------|------------------------|-----------------------|
| `institucion_previsional` | `codigo`, `nombre`, `tipo`, `rut`, `enlace` | `PRIMARY KEY (codigo)`, `UNIQUE (nombre)`, `CHECK tipo IN ('abierta','cerrada')`, `CHECK rut ~ '^[0-9]{1,8}-[0-9Kk]$'` | Referenciada por `plan_salud`, `persona` |
| `firma` | `firma_id`, `ruta` | `PRIMARY KEY (firma_id)`, `UNIQUE (ruta)` | Referenciada opcionalmente por `persona.firma_id` |
| `plan_salud` | `institucion_nombre`, `grupo`, `bonificacion` | `PRIMARY KEY (institucion_nombre, grupo)`, `CHECK (bonificacion BETWEEN 0 AND 100)` | `FOREIGN KEY (institucion_nombre)` → `institucion_previsional(nombre)` |
| `persona` | `persona_id`, `run`, `nombre`, `apellidos`, `direccion`, `correo`, `telefono`, `tipo_persona`, `titular_run`, `rol`, `profesion`, `especialidad`, `firma_id`, `institucion_nombre` | `PRIMARY KEY (persona_id)`, `UNIQUE (run)`, `CHECK telefono ~ '^[0-9]{9}$'`, `CHECK tipo_persona IN ('titular','beneficiario')`, reglas de consistencia titular/beneficiario, FK a `firma` y `institucion_previsional` | Autorreferencia en `titular_run`, base para `persona_plan`, `atencion` |
| `persona_plan` | `persona_id`, `institucion_nombre`, `grupo`, `es_titular`, `fecha_inicio`, `fecha_fin` | `PRIMARY KEY (persona_id, institucion_nombre, grupo)`, `CHECK (fecha_fin >= fecha_inicio OR fecha_fin IS NULL)` | `FOREIGN KEY (institucion_nombre, grupo)` → `plan_salud`; `FOREIGN KEY (persona_id)` → `persona` |
| `prestacion` | `prestacion_id`, `codigo_fonasa`, `codigo_adicional`, `codigo_fonasa_full`, `descripcion`, `grupo`, `tipo`, `valor_fonasa` | `PRIMARY KEY (prestacion_id)`, `UNIQUE (codigo_fonasa_full)`, `CHECK (valor_fonasa >= 0)` | Referenciada por `arancel_dc` y para conciliaciones con DC Colita |
| `arancel_dc` | `codigo_interno`, `codigo_fonasa`, `descripcion`, `valor_dc` | `PRIMARY KEY (codigo_interno)`, `CHECK (valor_dc >= 0)` | `FOREIGN KEY (codigo_fonasa)` → `prestacion(codigo_fonasa_full)`; utilizada por `orden` |
| `farmaco` | `farmaco_codigo`, `nombre_generico`, `descripcion`, `tipo`, `codigo_onu`, `clasificacion_onu`, `clasificacion_interna`, `estado`, `es_canasta_esencial`, `precio` | `PRIMARY KEY (farmaco_codigo)`, `CHECK estado IN ('activo','inactivo')`, `CHECK (precio IS NULL OR precio >= 0)` | Referenciada opcionalmente por `medicamento_prescrito` |
| `atencion` | `atencion_id`, `fecha`, `paciente_run`, `profesional_run`, `diagnostico`, `efectuada` | `PRIMARY KEY (atencion_id)` | `FOREIGN KEY (paciente_run)` → `persona(run)`; `FOREIGN KEY (profesional_run)` → `persona(run)`; base para `orden` y `medicamento_prescrito` |
| `orden` | `atencion_id`, `codigo_arancel`, `descripcion` | `PRIMARY KEY (atencion_id, codigo_arancel, descripcion)` | `FOREIGN KEY (atencion_id)` → `atencion`; `FOREIGN KEY (codigo_arancel)` → `arancel_dc` |
| `medicamento_prescrito` | `atencion_id`, `medicamento_nombre`, `farmaco_codigo`, `posologia`, `es_psicotropico` | `PRIMARY KEY (atencion_id, medicamento_nombre)`, `UNIQUE (atencion_id, farmaco_codigo)`, `CHECK (es_psicotropico IN (TRUE,FALSE))` | `FOREIGN KEY (atencion_id)` → `atencion`; `FOREIGN KEY (farmaco_codigo)` → `farmaco` (opcional) |


---

### 1 Arquitectura general del proceso


| Ubicación | Rol dentro del flujo | Archivos o subcarpetas clave |
|-----------|----------------------|------------------------------|
| `main.php` | Orquestador general que ejecuta en secuencia los filtros según dependencias. | Invoca a cada script PHP y controla la generación de OK/ERR/LOG. |
| `RequestPHP/` | Núcleo de depuración por entidad. | `filtroPersona.php`, `filtroInstituciones.php`, `filtroFarmacia.php`, `filtroMedicamento.php`, `filtroArancelFonasa.php`, `filtroArancel_DCColita.php`, `filtroPlanes.php`, `filtroMaestroPlanes.php`, `filtroAtencion.php`, `filtroOrden.php`. |
| `Old/` | Repositorio inalterado de las fuentes originales que vienen desde el enunciado. | CSV base por entidad más las subcarpetas `firmas/` y `planes/`. |
| `Depurado/` | Resultado limpio listo para inserción en la base de datos. | Archivos `*_OK.csv` y subcarpetas `firmas/`, `planes/` con resultados depurados. |
| `Eliminado/` | Resguarda los registros descartados para trazabilidad. | Archivos `*_ERR.csv` y estructura espejo de `firmas/`, `planes/`. |
| `Logs/` | Bitácoras detalladas de cada corrección aplicada por los filtros. | Archivos `*_LOG.txt` y subcarpeta `planes/` con los reportes de cada plan. |
| `RegistrosSQL/` | Evidencia de cargas realizadas sobre la base. | `cargaLOG.txt` con el registro de ejecuciones. |
| `RequestSQL/` | Scripts SQL de levantamiento de la BD y creación de tablas. | `levantamiento_BD.sql`. |
| `carga.sql` | Script SQL auxiliar para poblar tablas con los datos depurados. | Se ejecuta tras la depuración para insertar los `*_OK.csv` llamando a `levantamiento_BD.sql`. |
| `ImagenesERD/` | Diagramas de PGAdmin. | Diagramas ERD del proyecto. |

Esta arquitectura permite ejecutar, auditar y volver a correr cualquier depuración de forma modular, manteniendo separados el origen, los resultados limpios, los descartes, los scripts y los insumos de soporte.

---

## 2 Depuracion de datos con PHP

#### Flujo general del sistema

1. **Entrada:** se leen los archivos fuente desde la carpeta `Old/`.  
2. **Procesamiento:** el php específico depura los datos.  
3. **Salida:** se generan tres archivos:
   - `*_OK.csv` → datos listos para cargar.  
   - `*_ERR.csv` → datos descartados.  
   - `*_LOG.txt` → registro detallado de las operaciones realizadas.  
4. **Ejecución modular:** cada php puede ejecutarse individualmente o todos en cadena mediante `main.php`que ejecuta todos.

---

### 2.1. Explicación `RequestPHP/`

Esta carpeta agrupa los PHP, que se puede ejecutar solo o en conjunto mediante `main.php`, manteniendo la solución modular.

#### 2.1.1. Explicación `filtroPersona.php`

Lee `Old/Persona.csv` y escribe `Depurado/Persona_OK.csv`, `Eliminado/Persona_ERR.csv` y `Logs/Persona_LOG.txt`. Sus pasos principales son:

- **RUN y duplicados:** limpia formato (sin puntos, con DV módulo 11), valida longitud 6–8 dígitos y descarta duplicados o RUN irreparables enviándolos a `ERR`.
- **Datos de contacto:** capitaliza nombres y apellidos, compacta la dirección, normaliza correos (minúsculas, sin tildes ni caracteres raros, fallback `mail@invalido.com`) y estandariza teléfonos a 9 dígitos chilenos (rellenando con `111111111` cuando no se pueden recuperar).
- **Relación titular/beneficiario:** ajusta el tipo según la información disponible, fuerza que los titulares usen su propio RUN y descarta beneficiarios sin titular válido o con titular igual a su RUN.
- **Rol profesional:** mapea a un catálogo permitido (paciente por defecto), homologa alias y limpia profesión/especialidad cuando el rol no es clínico.
- **Firma e institución:** reemplaza rutas vacías por `sin firma`, marca como `no existe` las firmas que no están en `Old/firmas/` y asegura que la institución pertenezca al catálogo; de lo contrario usa **FONASA**.


#### 2.1.2. Explicación `filtroFarmacia.php`

Transforma `Old/Farmacia.csv` para generar `Farmacia_OK.csv`, `Farmacia_ERR.csv` y `Farmacia_LOG.txt`. Los nervios del script son:

- **Código base:** conserva solo dígitos, rechaza vacíos o duplicados y documenta cualquier corrección antes de guardar.
- **Textos controlados:** asigna valores por defecto para nombre/descripcion/clasificación, trunca a las longitudes del esquema y limpia caracteres invisibles.
- **Catálogos cerrados:** normaliza `tipo` a una lista conocida (fármacos, insumos, etc.), fuerza `estado` a `activo` o `inactivo` y traduce el flag de esencial a `0`/`1`.
- **Campos numéricos:** depura el código ONU y elimina su clasificación asociada cuando queda vacío; también sanitiza el precio a un entero positivo.
- **Registro de cambios:** toda fila válida se escribe en `OK` con el detalle de ajustes en el log; las filas vacías, sin código o repetidas van a `ERR`.

#### 2.1.3. Explicación `filtroInstituciones.php`

Depura `Old/Instituciones previsionales de salud.csv`, generando los archivos `_OK`, `_ERR` y `_LOG` correspondientes. Las reglas esenciales son:

- **Cabecera y filas vacías:** limpia el BOM del encabezado y descarta filas completamente en blanco.
- **Nombre y tipo:** capitaliza el nombre y fuerza el tipo a `abierta` o `cerrada`, usando `abierta` como fallback.
- **Enlace:** elimina el prefijo `http(s)://`, baja a minúsculas y quita barras finales para tener URLs uniformes.
- **RUT:** estandariza formato con guion, recalcula el dígito verificador vía módulo 11 y descarta los RUT imposibles, registrando la corrección en el log.

Así queda un catálogo de instituciones listo para referenciar desde personas y otros filtros sin inconsistencias.


#### 2.1.4. Explicación `filtroArancel_DCColita.php`
Depura `Old/Arancel DCColita de rana.csv` y genera `Arancel_DCColita_OK/ERR/LOG` con estas reglas:

- **Código interno único:** conserva solo dígitos, descarta filas sin código y agrupa duplicados para quedarse con la versión más limpia; las restantes se mandan a `ERR`.
- **Código Fonasa válido:** elimina espacios y puntos, exige el patrón `entero` o `entero-entero` y rechaza formatos inválidos.
- **Descripción y valor:** compacta espacios, recorta la atención a 100 caracteres y obliga al valor a ser un entero positivo.
- **Bitácora clara:** deja rastro en el log de cada corrección y del motivo por el que una fila termina en `ERR`.

#### 2.1.5. Explicación `filtroArancelFonasa.php`
Normaliza `Old/Arancel fonasa.csv`, dejando los archivos `Arancel_Fonasa_OK/ERR/LOG`:

- **codF obligatorio y único:** limpia a dígitos, remueve ceros sobrantes y descarta filas sin código o duplicadas.
- **codA opcional:** se queda con los dígitos válidos y, si no queda nada, registra cadena vacía.
- **Texto y números consistentes:** la atención se compacta y recorta a 100 caracteres; el valor queda como entero positivo.
- **Clasificación controlada:** `grupo` y `tipo` se normalizan sin espacios dobles y se limitan a 30 caracteres.
- **Errores explicados:** cualquier ausencia crítica (código, atención o valor) mueve la fila a `ERR` con su motivo en el log.

#### 2.1.6. Explicación `filtroAtencion.php`

Toma `Old/Atencion.csv` y produce `Atencion_OK/ERR/LOG`. El flujo central:

- **ID único:** quita caracteres no numéricos, exige presencia y descarta duplicados.
- **Fecha ISO:** transforma `dd-mm-yy` a `YYYY-MM-DD`; fechas inválidas se van a `ERR`.
- **RUN validados:** normaliza paciente y médico con módulo 11 y verifica que existan en `Persona_OK.csv`.
- **Diagnóstico vs. efectuada:** limpia mojibake, trunca a 100 caracteres y exige diagnóstico cuando `efectuada=TRUE`; si es `FALSE`, lo deja vacío.
- **Estado de atención:** acepta solo `TRUE` o `FALSE`, documentando cualquier ajuste en el log.

#### 2.1.7. Explicación `filtroMedicamento.php`

Depura `Old/Medicamento.csv`, generando `Medicamento_OK/ERR/LOG` con estas reglas:

- **Referencia válida:** limpia `IDAtencion`, lo fuerza a entero y comprueba que exista en `Atencion_OK.csv`.
- **Textos sanitizados:** normaliza medicamento y posología (mojibake, espacios, longitud 100). Usa `SIN NOMBRE` y `Sin posologia` como valores seguros cuando vienen vacíos.
- **Bandera psicotrópico:** mapea variantes (`1/0`, `si/no`) a `TRUE`/`FALSE`; otras entradas provocan descarte.
- **Trazabilidad:** cada corrección queda registrada, y cuando falta la referencia o el booleano es inválido, la fila pasa a `_ERR`.

#### 2.1.8. Explicación `filtroOrden.php`

Limpia `Old/Orden.csv` generando `Orden_OK/ERR/LOG` con foco en claves foráneas consistentes:

- **IDs cruzados:** normaliza `IDAtencion` y `IDArancel` a enteros y verifica que existan en `Atencion_OK.csv` y `Arancel_DCColita_OK.csv`; si faltan, la fila va a `ERR`.
- **Inferencia por descripción:** cuando el arancel numérico no existe, intenta mapearlo usando la descripción normalizada; si encuentra una sola coincidencia, reemplaza el código.
- **Consulta médica:** corrige mojibake, compacta espacios, trunca a 100 caracteres y usa `Sin descripción` como fallback cuando llega vacía o `NULL`.
- **Reporte detallado:** el log indica cada ajuste (IDs recalculados, truncados, mapeos por descripción) y los motivos de descarte, lo que facilita auditar los errores más frecuentes.

#### 2.1.9. Explicación `filtroPlanes.php`

Lee cada CSV de `Old/planes/` y genera, por archivo, un trío de salidas en `Depurado/planes/`, `Eliminado/planes/` y `Logs/planes/`:

- **Normalización por archivo:** corrige codificación Latin-1, fija la cabecera a `bonificacion;grupo` y procesa fila a fila.
- **Bonificación controlada:** limpia símbolos, convierte a entero, fuerza el rango 0–100 (vacíos o `NULL` → 0, extremos saturados) y registra cada ajuste.
- **Grupo higienizado:** repara mojibake, compacta espacios y trunca a 100 caracteres; cuando falta queda `Sin grupo`.
- **Gestión de filas vacías:** cualquier registro totalmente vacío se deriva al CSV `*_ERR.csv` del plan, preservando también el log con el motivo.

El resultado son archivos `_OK` homogéneos por institución, listos para ser consolidados en el maestro.

#### 2.1.10. Explicación `filtroMaestroPlanes.php`

Agrupa los `*_OK.csv` creados en el paso anterior dentro de `Depurado/planes/` y construye un `MaestroPlanes_OK.csv`, junto con un log y un reporte de incidencias:

- **Iteración ordenada:** revisa cada archivo del directorio, infiere el nombre de la institución desde el nombre del archivo y copia sus filas válidas.
- **Validación estructural:** exige que existan las columnas `bonificacion` y `grupo`; cuando faltan o el archivo está vacío, lo anota en `MaestroPlanes_ERR.csv`.
- **Resumen de ejecución:** el log central enumera los planes procesados, las filas consolidadas y los casos omitidos (por ejemplo, archivos ilegibles o sin datos).

Así se obtiene un catálogo único y trazable de planes depurados para la etapa de carga.


#### 2.1.11. Explicación `main.php`

Orquesta todos los scripts de `RequestPHP/` en un solo comando (`php main.php`) y coordina la copia final de firmas:

- **Lista de ejecución fija:** mantiene un arreglo `$SCRIPTS` con el orden dependiente (instituciones → personas → catálogos → atenciones → medicamentos/órdenes → planes).
- **Monitoreo de salida:** para cada filtro abre un proceso `php`, captura stdout/stderr y detiene la cadena si algún script retorna un código distinto de cero.
- **Post-proceso:** al terminar sin errores, replica `Old/firmas/` hacia `Depurado/firmas/` para que las rutas corregidas en personas tengan archivos disponibles.
- **Extensibilidad:** basta con agregar un nuevo nombre al arreglo para integrarlo en la ejecución secuencial.

---

## 3 Carga de Datos en PostgreSQL

Una vez generados los CSV depurados, la carga completa se automatiza en PostgreSQL. El objetivo es reconstruir el esquema lógico desde cero, poblarlo en el orden correcto y dejar evidencia de cada paso sin intervención manual.

### 3.1 Archivos `.sql`

El proceso se desprende en dos scripts:

- `carga.sql` es el punto de entrada que puede ejecutar fácilmente cualquier corrector (`psql -f carga.sql`).
- `RequestSQL/levantamiento_BD.sql` contiene todo el DDL, las sentencias de carga y la bitácora generada durante la ejecución.

#### 3.1.1 Explicación `carga.sql`

`carga.sql` actúa como envoltorio ligero para el script principal:

- Habilita `\set ON_ERROR_STOP on` para detener la ejecución ante el primer error y evitar estados intermedios.
- Muestra mensajes de inicio y término con `\echo`, de modo que la consola evidencie qué archivo se está ejecutando.
- Incluye (`\include`) `RequestSQL/levantamiento_BD.sql`, concentrando todo el trabajo pesado en un solo archivo reutilizable.

#### 3.1.2 Explicación `RequestSQL/levantamiento_BD.sql`

Este script encapsula la creación del esquema `dccolita`, la carga ordenada de los CSV y la generación de un log detallado:

- **Bootstrap del esquema:** abre una transacción (`BEGIN … COMMIT`), elimina cualquier versión previa del schema y lo recrea antes de fijar el `search_path`, garantizando que la entrega siempre parte de una base limpia.
- **Definición en 3FN/BCNF:** crea las tablas con sus claves primarias, foráneas e índices auxiliares. Destacan reglas de negocio como `plan_bonificacion_ck` (bonos 0–100), `persona_titular_consistencia_ck` (consistencia titular/beneficiario), `arancel_dc_prestacion_fk` (códigos propios enlazados a Fonasa) y `medicamento_prescrito_farmaco_unq` para evitar duplicidad por fármaco.
- **Carga mediante staging:** cada CSV se vuelca primero a una tabla temporal (`stg_*`) con `\copy`. Desde ahí se normalizan datos (trim, uppercase/lowercase, conversiones numéricas) antes de insertarlos en la tabla definitiva. Ejemplos concretos:
  - `stg_institucion_previsional` homogeniza nombres y tipos antes de poblar `institucion_previsional`.
  - `stg_plan_salud` cruza el maestro consolidado con alias normalizados para empatar los planes con sus instituciones.
  - `stg_persona` permite registrar firmas únicas en la tabla `firma` y luego insertar personas con teléfonos validados, RUN en mayúscula y referencias institucionales.
  - `stg_prestacion`, `stg_arancel_dc` y `stg_farmaco` controlan la conversión de enteros y la limpieza de descripciones antes de llegar a sus catálogos definitivos.
- **Relaciones derivadas y validaciones cruzadas:** después de poblar las tablas base se generan asociaciones y se filtra información inconsistente:
  - `persona_plan` se arma a partir de la institución declarada en `persona`, marcando quién es titular.
  - Las atenciones se insertan solo si el paciente y el profesional existen en `persona`, y las órdenes se cruzan con `arancel_dc`.
  - Para medicamentos se construye un índice auxiliar `farmaco_idx` que normaliza nombres (sin acentos ni símbolos) y permite mapear cada prescripción con su código de catálogo; además se evita cargar registros cuya atención nunca existió.
  - Se registra una verificación explícita entre códigos Fonasa propios y los oficiales (`[prestacion_vs_arancel] coincidencias…`).
- **Bitácora integrada:** una tabla temporal `carga_log` almacena el resultado de cada bloque (`staging`, `insertadas`, descartes, estructura final). Al cerrar la transacción, el script exporta esa bitácora a `RegistrosSQL/cargaLOG.txt`, dejando trazabilidad de todo lo que ocurrió durante la carga sin requerir pasos extra.

Con esta orquestación, la base queda lista para pruebas y consultas en un solo paso reproducible.

---

## 4 Resumen de correcciones y trazabilidad

| Archivo / Entidad | Problema detectado | Acción aplicada | Resuelto por |
|-------------------|--------------------|-----------------|--------------|
| `Persona.csv` | RUN con formato inválido, correos mal formados, beneficiarios sin titular | Normalización de RUN/DV, limpieza de correos y teléfonos, descarte de beneficiarios inconsistentes | PHP (`filtroPersona.php`) |
| `Instituciones previsionales de salud.csv` | RUT mal formados, enlaces con protocolo y espacios extra | Limpieza de cabeceras, normalización de enlaces, validación módulo 11 (descarta irrecuperables) | PHP (`filtroInstituciones.php`) |
| `Farmacia.csv` | Códigos vacíos o duplicados, textos con mojibake, valores fuera de dominio | Validación de código único, truncado de campos, homogenización de tipo/estado/esencial, registros irreparables a `_ERR` | PHP (`filtroFarmacia.php`) |
| `Arancel DCColita de rana.csv` | Códigos Fonasa inválidos, descripciones largas, duplicados por código interno | Selección de mejor versión por código, truncado a 100 caracteres, descarte de entradas sin valor | PHP (`filtroArancel_DCColita.php`) |
| `Arancel fonasa.csv` | `codF` con puntuación, duplicados y valores de grupo/tipo extensos | Limpieza de códigos, control de duplicados, truncado a longitudes del esquema | PHP (`filtroArancelFonasa.php`) |
| `Atencion.csv` | IDs duplicados, fechas fuera de formato, RUN sin referencia en personas | Normalización de fecha ISO, validación de RUN, descarte de atenciones sin paciente o médico válido | PHP (`filtroAtencion.php`) |
| `Medicamento.csv` | Atenciones inexistentes, nombres vacíos, banderas psicotrópicas fuera de dominio | Mapear a catálogo de farmacia, forzar valores seguros (`SIN NOMBRE`, `Sin posologia`), descartar filas sin FK | PHP (`filtroMedicamento.php`) |
| `Orden.csv` | Arancel inexistente para la descripción, atenciones sin referencia | Inferencia por descripción, descarte de registros sin FK válida | PHP (`filtroOrden.php`) |
| `planes/*.csv` | Bonificaciones fuera de rango, textos con codificación mixta, filas vacías | Conversión a enteros 0–100, normalización de texto, separación de `_ERR` por plan | PHP (`filtroPlanes.php`) |
| `MaestroPlanes_OK.csv` | Archivos parciales o con cabeceras incorrectas | Verificación de columnas obligatorias, log central de incidencias y recuento de filas | PHP (`filtroMaestroPlanes.php`) |
| Carga SQL | Atenciones sin referencias válidas, medicamentos sin atención asociada | Filtros sobre staging antes de insertar (`descartadas`, `sin_atencion` en `cargaLOG.txt`) | SQL (`RequestSQL/levantamiento_BD.sql`) |

Los detalles específicos (líneas afectadas y mensajes) quedan registrados en cada `*_LOG.txt` generado por PHP y en `RegistrosSQL/cargaLOG.txt` para la etapa de carga.

---

## 5 Instrucciones de ejecución

1. **Depuración de archivos CSV**
   ```bash
   php main.php
   ```
   Ejecutar desde la raíz `E3/`. Genera/actualiza las carpetas `Depurado/`, `Eliminado/` y `Logs/` con la versión más reciente de los `*_OK/ERR/LOG`.

2. **Levantamiento y carga en PostgreSQL**
   ```bash
   psql -f carga.sql
   ```
   Debe ejecutarse con un usuario que tenga permisos para crear el esquema `dccolita`. El proceso recrea el esquema, carga todos los CSV depurados y escribe la bitácora en `RegistrosSQL/cargaLOG.txt`. Si ocurre un error, la ejecución se detiene (`\set ON_ERROR_STOP on`); revisar la consola y corregir antes de relanzar.

Ejecutando ambos comandos en orden (`php main.php` → `psql -f carga.sql`) se completa el pipeline exigido por el encargo utilizando únicamente los archivos finales de este directorio.

---

## 6 Referencias y recursos consultados

Durante el desarrollo se revisaron y adaptaron fragmentos de código y buenas prácticas compartidas por la comunidad. Las fuentes más relevantes fueron:

- **Stack Overflow** – se reutilizaron patrones publicados en hilos sobre validación del RUN chileno, normalización de CSV con `fgetcsv` y sanitización de texto multibyte en PHP; búsquedas clave: [`php validar rut chileno`](https://stackoverflow.com/search?q=php+validar+rut+chileno), [`php sanitize csv`](https://stackoverflow.com/search?q=php+sanitize+csv).
- **Documentación oficial de PHP** – referencia continua para funciones utilizadas en los filtros (`filter_var`, `preg_replace`, `iconv`, `DateTime`): https://www.php.net/manual/es/.
- **Documentación de PostgreSQL** – guías para `COPY`, restricciones (`CHECK`, `UNIQUE`) y control de transacciones: https://www.postgresql.org/docs/current/.
- **ISO 8601:2004** – lineamientos sobre formato de fechas (`YYYY-MM-DD`) aplicados en `filtroAtencion.php` y en la carga: https://www.iso.org/iso-8601-date-and-time-format.html.
- **Guía de tratamiento de archivos CSV en UTF-8** – recomendaciones para manejar codificaciones y normalizar caracteres especiales antes de la carga: https://developer.mozilla.org/en-US/docs/Glossary/UTF-8.
- **Blog "Data to Fish" – Tutoriales de limpieza de datos con PHP y SQL** – ideas para estructurar logs de errores y reportes por entidad: https://datatofish.com/.

Estas referencias ayudaron a fundamentar las decisiones de depuración y a documentar los bloques en los que se adaptaron soluciones propuestas por otros desarrolladores.
