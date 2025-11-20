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
Para documentar el avance de la sección 1 del enunciado cree la carpeta `Encargos (14pts)/`, donde quedarán los SQL. 

(a) ya esta resuelto en `a_indice2RUN.sql`, que crea:

- `idx_persona_run` sobre `"Persona"."RUN"`
- `idx_institucion_salud_rut` sobre `"InstituciondeSalud"."RUT"`
ambos mediante `CREATE INDEX ...`.



