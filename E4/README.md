# Proyecto semestral Etapa 4 2025-2   
**De:** Santiago Díaz Edwards

## Pasos previos (contexto)...

### Objetivo de la etapa
El objetivo de esta etapa final del proyecto es que el estudiante ponga en pr´actica sus
conocimientos en bases de datos integradas dentro de un lenguaje de programaci´on, mediante
la creaci´on de una aplicaci´on web-PHP, muy simple, para interactuar con la base de datos
y haga uso de los de ´Indices, Transacciones, Vistas, Stored Procedures y Triggers vistos en
clases.
### 1. Contexto
Usted cuenta con la base de datos de DCColita de rana validada y cargada. En esta etapa
el centro médico va a operar con un programa para las siguientes acciones: 
i) A través de un usuario administrativo (recepcionista o call center) reservar una hora medica
ii) Cancelarla.
iii) Cuando el paciente llega a su hora, el/la recepcionista atiende al paciente y genera el
bono (boleta) de atención. 

Por otra parte, la aplicación permite al médico ver la ficha del paciente (solo atenciones anteriores y diagnósticos) ingresar un nuevo diagnóstico, generar
las recetas y órdenes en archivos para imprimir y registrar todo en la base.

## Proceso personal
Antes que nada devemos cargar el `DumpE4.sql`

```bash
# Crear la base solo una vez
CREATE DATABASE entrega_numero_4_srde

# Primero cargar el dump dentro del directorio
psql -h localhost -p 5432 -U santiago -d entrega_numero_4_srde -f DumpE4.sql
```

Esto es especifico para mi caso.

## Carpeta `Encargos (14pts)`
Para documentar el avance de la sección 1 del enunciado (14 pts) se creó la carpeta `Encargos (14pts)/`, donde quedarán los SQL:

1. Scripts para los índices secundarios sobre todos los campos `RUN` y ejemplos de uso en consultas.
2. El índice primario compuesto sobre `Agenda(IDMedico, dia, hora)`.
3. Bloques `BEGIN; ... COMMIT;` que demuestran las transacciones exigidas para inserciones/actualizaciones críticas.
4. El stored procedure que genera recetas/órdenes con los textos fijos y variables definidos por el enunciado.
5. El trigger que invoca ese SP cada vez que se cierre una atención (diagnóstico + recetas + órdenes).
6. La vista `Ficha` con las atenciones ordenadas de más reciente a más antigua, incluyendo fecha, médico, especialidad y diagnóstico.
7. Ejemplos de validación de formato/dominio y mitigación de SQL Injection para todos los formularios de ingreso.

Cada archivo incluirá comentarios explicando las decisiones tomadas para que la sección de desarrollo posterior referencie directamente el script asociado.
