# Entrega 3 – Depuración de Datos  
**Proyecto:** *DC Colita de Rana*  
**Curso:** IIC2413 – Bases de Datos 2025-2  
**Profesor:** Eduardo Bustos  
**Ayudante:** Martina Molina  
**Alumno:** Santiago [Tu Apellido]

---

## 1. Objetivo -Depuración de Datos

Esta entrega corresponde a la **Etapa 3 del proyecto semestral**, cuyo propósito es **depurar los archivos CSV entregados por el centro médico DC Colita de Rana** y dejarlos listos para su carga en la base de datos PostgreSQL.  

El proceso se enfoca en garantizar **consistencia, formato y validez referencial**, manteniendo un registro detallado de cada cambio en los archivos *LOG*.

A diferencia de etapas anteriores, esta versión implementa una **arquitectura modular**, donde cada dataset tiene su propio script de depuración y un archivo `main.php` que los coordina todos.

---

### 1.2. Arquitectura general del proceso

El flujo de trabajo se estructura en torno a scripts PHP especializados por entidad, acompañados por un módulo principal que los invoca en orden de dependencia:

```bash
main.php                     # Orquestador general (ejecuta todos los depurados)
RequestPHP/
├── filtroPersona.php        # Depuración de Personas
├── filtroInstituciones.php  # Depuración de Instituciones previsionales
├── filtroFarmacia.php       # Depuración de Farmacia
├── filtroMedicamento.php    # Depuración de Medicamentos
├── filtroArancel.php        # Depuración de Aranceles (Fonasa / DCColita)
├── filtroPlanes.php         # Depuración de Planes de Isapre
├── filtroAtencion.php       # Depuración de Atenciones
├── filtroOrden.php          # Depuración de Órdenes y Recetas
└── utils.php                # Funciones comunes (normalización, validación, logs)
```

---

### 1.3. Estructura y funcionamiento del directorio

El directorio `E3/` está organizado para reflejar el flujo completo de depuración, desde los archivos originales hasta los resultados limpios y los registros de trazabilidad.  

Cada carpeta tiene una función específica:

| Carpeta | Función | Contenido principal, ejemplos... |
|----------|----------|--------------------|
| **Old/** | Contiene los archivos originales sin modificar entregados por el enunciado. | `Persona.csv`, `Farmacia.csv`, `Medicamento.csv`, `Atencion.csv`, etc. |
| **Depurado/** | Contiene los archivos limpios y listos para ser cargados en la base de datos. | `Persona_OK.csv`, `Instituciones_OK.csv`, etc. |
| **Eliminado/** | Contiene los registros descartados durante la depuración (datos irrecuperables o con errores críticos). | `Persona_ERR.csv`, etc. |
| **Logs/** | Registra las acciones realizadas por los scripts: correcciones, inferencias, reemplazos, errores. | `Persona_LOG.txt`, `Farmacia_LOG.txt`, etc. |
| **RequestPHP/** | Contiene los scripts PHP que ejecutan la depuración. Incluye uno por entidad y un controlador principal (`main.php`). | `filtroPersona.php`, `filtroArancel.php`, `main.php`, etc. |

---

#### Flujo general del sistema

1. **Entrada:** se leen los archivos fuente desde la carpeta `Old/`.  
2. **Procesamiento:** el script específico limpia, corrige, valida y clasifica los datos.  
3. **Salida:** se generan tres archivos:
   - `<Entidad>_OK.csv` → datos listos para cargar.  
   - `<Entidad>_ERR.csv` → datos descartados.  
   - `<Entidad>_LOG.txt` → registro detallado de las operaciones realizadas.  
4. **Ejecución modular:** cada script puede ejecutarse individualmente o todos en cadena mediante `main.php`.

---
### 1.4. Explicación `RequestPHP/`

La carpeta **`RequestPHP/`** contiene todos los scripts PHP responsables del proceso de depuración.  
Cada script se encarga de limpiar, validar y normalizar los datos de un archivo específico, siguiendo las reglas de negocio definidas para el sistema del centro médico **DC Colita de Rana**.

Esta carpeta cumple un rol **modular y escalable**, ya que permite ejecutar cada depuración de forma independiente o combinada a través del archivo principal `main.php`.  
De esta forma, cualquier mejora o ajuste en la lógica de limpieza de un archivo no afecta al resto.

### 1.4.1. Explicación `filtroPersona.php`

El script `filtroPersona.php` es el encargado de leer el archivo original `Old/Persona.csv`, depurar sus registros y generar tres salidas:

- **`Depurado/Persona_OK.csv`** – contiene todas las filas que se pudieron reparar o normalizar.  
- **`Eliminado/Persona_ERR.csv`** – recoge los registros irrecuperables (RUN mal formado con menos de seis dígitos o totalmente no numérico, o duplicados exactos de RUN) que no deben cargarse en la base.  
- **`Logs/Persona_LOG.txt`** – registra cada corrección aplicada o anomalía detectada para permitir su trazabilidad.  

A grandes rasgos, el proceso que realiza el script es el siguiente:

#### Lectura y configuración  
Se identifica la ruta del archivo `Persona.csv` en la carpeta `Old/`, se aseguran las carpetas de salida (`Depurado`, `Eliminado`, `Logs`) y se abre cada uno de los ficheros correspondientes.  
También se construye un **diccionario de columnas** para localizar cada campo de forma dinámica, lo que permite adaptarse a variaciones en los nombres de las cabeceras.

#### Normalización del RUN  
Se limpia el RUN de puntos y espacios, se separan el cuerpo y el dígito verificador, y se valida que el cuerpo tenga al menos 6 dígitos y no más de 8 (si hay más, se toman los últimos ocho).  
Se calcula el dígito verificador con el algoritmo **módulo-11** y, si no coincide con el proporcionado, se corrige.  
Los RUN duplicados se envían a `ERR` y se registra el motivo en el log.

#### Corrección de nombres y apellidos  
Se capitalizan los nombres y apellidos (cada palabra con mayúscula inicial) y se eliminan espacios extra.  
Si alguno de estos campos está vacío, se reemplaza por **“Sin Nombre”** o **“Sin Apellidos”** para evitar nulos.

#### Dirección  
Se compactan los espacios repetidos sin modificar el contenido.

#### Correo electrónico  
Se convierten a minúsculas, se eliminan caracteres no permitidos y diacríticos, y se valida que tengan una sola `@` y un dominio con al menos un punto.  
Cuando el correo no es reparable o viene vacío, se sustituye por `mail@invalido.com` y se documenta la corrección en el log.

#### Teléfono  
Se extraen todos los dígitos y se elimina el prefijo `56` si aparece.  
Se conservan los últimos 9 dígitos (formato de telefonía chilena); si el resultado no alcanza esa longitud, se reemplaza por `111111111` y se registra en el log.

####  Tipo y titular  
Se valida el campo `tipo` (debe ser **titular** o **beneficiario**).  
Si está vacío o es inválido, se infiere:  
- Se asigna **titular** cuando el RUN del titular coincide con el RUN de la persona o no existe un titular válido.  
- Se asigna **beneficiario** en caso contrario.  

El campo `titular` también se normaliza como RUN; si es inválido y el tipo es beneficiario, se reemplaza por el RUN de la persona y se deja constancia en el log.  
Para las filas con tipo titular, el campo titular siempre queda igual al RUN de la persona.

####  Rol, profesión y especialidad  
El campo `rol` se compara contra una lista de valores permitidos (*Paciente, Staff Médico, Administrativo, Enfermero(a)*, etc.).  
Los roles no reconocidos se sustituyen por **Paciente**.  
Si el rol no es clínico, los campos de profesión y especialidad se vacían; de lo contrario, se capitalizan.  
También se corrigen alias comunes (por ejemplo, *Paramedico* → *Paramédico*).

#### Firma  
Se mantiene el valor del campo firma tras eliminar espacios; no se valida la existencia del archivo aquí (esa verificación puede hacerse en otra etapa si se requiere).

#### Institución previsional de salud  
Se normaliza el nombre y se consulta contra el catálogo de instituciones (cargado desde `Instituciones previsionales de salud.csv` lo puse manualmente no revisa como tal el csv).  
Si está vacío o no coincide con una entrada del catálogo, se reemplaza por **FONASA** y se anota la corrección en el log.
**Importante**: se anoto en Fonasa porque es el servicio publico y la logica es que si no tiene institucion previsional es porque es Fonasa.

---

Cada fila procesada se escribe en `OK` o `ERR` dependiendo de si supera todas estas validaciones.  
Las acciones realizadas sobre cada registro (correcciones, inferencias o descartes) se van acumulando en un **array de mensajes**, que finalmente se vuelca en `Persona_LOG.txt` con una marca de tiempo y el número de línea original.  

De este modo, el script proporciona un **rastro claro y completo de lo ocurrido con cada dato**, dejando el CSV **listo para su carga en la base de datos** en la siguiente etapa del proyecto.

