# Diccionario de Tablas DCColita

Esta guía describe cada tabla del dump `DumpE4.sql`, su propósito dentro de la clínica, los campos clave y las relaciones declaradas en PostgreSQL 17. Usa este documento para diseñar consultas, validar integridad referencial y ubicar dónde deben aterrizar los cambios solicitados en la entrega.

## Agenda
- **Campos:** `ID` (médico), `Fecha` y `Hora`, todos NOT NULL.
- **Llave primaria:** compuesta al crear el índice solicitado (ID, Fecha, Hora). En el dump llega sin PK formal.
- **Uso:** representa los bloques disponibles que cada médico puede ofrecer al call center. Se alimenta para poblar el menú de agendamiento y se “devuelve” cuando se cancela una atención.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `ID` | integer | NO | — (se convertirá en parte de la PK propuesta) |
| `Fecha` | date | NO | — |
| `Hora` | time | NO | — |

## Arancel
- **Campos relevantes:** `ID`, `Codigo` FONASA, `ConsAtMedica` (descripción), `ValorFonasa`, `ValorColita`, `Grupo`, `Tipo`.
- **Restricciones:** PK `Arancel_pkey` sobre `ID`. Referenciada por `Orden`.
- **Uso:** catálogo de prestaciones médicas. Permite calcular bonos según institución (ISAPRE/FONASA) y generar órdenes asociadas a atenciones.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `ID` | integer | NO | PK |
| `Codigo` | text | NO | — |
| `Codigo_a` | char | SI | — |
| `ConsAtMedica` | text | NO | — |
| `ValorFonasa` | integer | NO | — |
| `ValorColita` | integer | SI | — |
| `Grupo` | integer | SI | — |
| `Tipo` | text | SI | — |

## Atencion
- **Campos:** `ID`, `fecha`, `IDPaciente`, `IDMedico`, `Diagnostico`, `Efectuada` (bool), `hora`.
- **Restricciones:** PK `Atencion_pkey` (ID, IDPaciente, IDMedico) y unique `ID`. FKs `IDPaciente` y `IDMedico` → `Persona`.
- **Uso:** registro central del acto médico. Controla si la atención fue efectuada, sirve como FK para `Orden` y `medicamentos`, y dispara el trigger que imprimirá recetas/órdenes.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `ID` | integer | NO | PK + unique |
| `fecha` | date | NO | — |
| `IDPaciente` | integer | NO | PK + FK → `Persona.ID` |
| `IDMedico` | integer | NO | PK + FK → `Persona.ID` |
| `Diagnostico` | text | NO | — |
| `Efectuada` | boolean | NO | — (default false) |
| `hora` | time | SI | — |

## Farmacia
- **Campos:** `codigo`, `Nombre`, `descripcion`, `tipo`, `CodONU`, `ClasONU`, `Clasificacion`, `Estado`, `canastaEsencial`, `Precio`.
- **Restricciones:** PK `Farmacia_pkey` en `codigo`, unique `Nombre`, FK de `medicamentos`.
- **Uso:** catálogo de fármacos (incluye psicotrópicos y canasta esencial). Suministra la lista para el formulario médico.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `codigo` | integer | NO | PK |
| `Nombre` | text | NO | Unique |
| `descripcion` | text | NO | — |
| `tipo` | text | NO | — |
| `CodONU` | integer | SI | — |
| `ClasONU` | text | SI | — |
| `Clasificacion` | text | SI | — |
| `Estado` | text | NO | — |
| `canastaEsencial` | boolean | SI | — |
| `Precio` | integer | SI | — |

## Grupo
- **Campos:** `ID`, `Descripcion`.
- **Restricciones:** PK `Grupo_pkey`, FK de `Planes`.
- **Uso:** clasifica aranceles/planes según tablas de Fonasa, se usa junto a `Planes` para bonificaciones.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `ID` | integer | NO | PK |
| `Descripcion` | text | SI | — |

## InstituciondeSalud
- **Campos:** `ID`, `RUT`, `Codigo`, `Nombre`, `Tipo` (booleana: abierta/cerrada), `Enlace`.
- **Restricciones:** PK `InstituciondeSalud_pkey`, FKs en `Planes` e `InstSalud` dentro de `Persona`.
- **Uso:** catálogo de ISAPRE/FONASA para asociar pacientes y planes.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `ID` | integer | NO | PK |
| `RUT` | text | NO | — |
| `Codigo` | integer | NO | — |
| `Nombre` | text | NO | — |
| `Tipo` | boolean | NO | — |
| `Enlace` | text | SI | — |

## Orden
- **Campos:** `IDAtencion`, `IDArancel`.
- **Restricciones:** PK (`IDArancel`,`IDAtencion`), FKs hacia `Arancel` y `Atencion`.
- **Uso:** relaciona una atención con las órdenes/prescripciones de procedimientos (bonos impresos).
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `IDAtencion` | integer | NO | PK + FK → `Atencion.ID` |
| `IDArancel` | integer | NO | PK + FK → `Arancel.ID` |

## Persona
- **Campos:** `ID`, `RUN`, `Nombres`, `Apellidos`, `Direccion`, `email`, `telefono`, `InstSalud`, `medico`.
- **Restricciones:** PK `Persona_pkey`, FK `InstSalud` → `InstituciondeSalud`. Fuente para `Rol`, `beneficiario`, `profesion`, y las referencias `IDPaciente/IDMedico`.
- **Uso:** maestro único de individuos (pacientes, titulares, médicos). El booleano `medico` permite filtrar roles clínicos.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `ID` | integer | NO | PK |
| `RUN` | text | NO | — |
| `Nombres` | text | NO | — |
| `Apellidos` | text | NO | — |
| `Direccion` | text | SI | — |
| `email` | text | SI | — |
| `telefono` | text | SI | — |
| `InstSalud` | integer | SI | FK → `InstituciondeSalud.ID` |
| `medico` | boolean | SI | — |

## Planes
- **Campos:** `ID` (mismo ID que la institución), `Grupo`, `Bonificacion`.
- **Restricciones:** FKs a `InstituciondeSalud` y `Grupo`.
- **Uso:** describe los planes de salud ligados a la institución y porcentaje de bonificación utilizado en el cálculo del bono.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `ID` | integer | NO | FK → `InstituciondeSalud.ID` |
| `Grupo` | integer | SI | FK → `Grupo.ID` |
| `Bonificacion` | integer | SI | — |

## Rol
- **Campos:** `IDPersona`, `Rol`.
- **Restricciones:** FK `IDPersona` → `Persona`.
- **Uso:** etiquetas adicionales para cada persona (paciente, médico, administrativo), útiles al validar que una persona tenga rol paciented antes de agendar.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `IDPersona` | integer | NO | FK → `Persona.ID` |
| `Rol` | text | SI | — |

## beneficiario
- **Campos:** `IDpersona`, `Beneficiario` (bool), `IDtitular`.
- **Restricciones:** PK `beneficiario_pkey` en `IDpersona`, FKs `IDpersona` y `IDtitular` → `Persona`.
- **Uso:** define dependencias entre titulares y beneficiarios, necesario para validar cobertura al emitir bonos.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `IDpersona` | integer | NO | PK + FK → `Persona.ID` |
| `Beneficiario` | boolean | NO | — |
| `IDtitular` | integer | SI | FK → `Persona.ID` |

## medicamentos
- **Campos:** `IDAtencion`, `Medicamento`, `Posologia`, `Psicotropico`.
- **Restricciones:** PK (`IDAtencion`,`Medicamento`), FKs a `Atencion` y `Farmacia`.
- **Uso:** detalle de fármacos prescritos en cada atención; alimenta el SP que genera recetas electrónicas, respetando reglas para psicotrópicos.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `IDAtencion` | integer | NO | PK + FK → `Atencion.ID` |
| `Medicamento` | text | NO | PK + FK → `Farmacia.Nombre` |
| `Posologia` | text | NO | — |
| `Psicotropico` | boolean | SI | — |

## profesion
- **Campos:** `ID` (médico), `firma` (ruta de la PNG en `firmas/`), `profesion`.
- **Restricciones:** PK `medico-firma`, FK `ID` → `Persona`.
- **Uso:** guarda la firma digital y la subespecialidad usada al generar diagnósticos, recetas y órdenes personalizadas.
- **Detalle de columnas:**

| Columna | Tipo | Nulo | Llave/Referencia |
| --- | --- | --- | --- |
| `ID` | integer | NO | PK + FK → `Persona.ID` |
| `firma` | text | SI | — |
| `profesion` | text | SI | — |

> **Nota:** Aunque el dump define algunas restricciones como “NOT VALID”, deben revisitarse y validar antes de producción para garantizar la integridad referencial completa.
