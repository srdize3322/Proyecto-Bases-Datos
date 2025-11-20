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

### (a) resuelto en `a_indice2RUN.sql`
Genera índices secundarios para acelerar búsquedas de RUN y RUT:
- `idx_persona_run` sobre `"Persona"."RUN"` (login, fichas, beneficiarios).
- `idx_institucion_salud_rut` sobre `"InstituciondeSalud"."RUT"` (planes y afiliaciones).

### (b) resuelto en `b_indice_agenda.sql`
Define la PK compuesta de `Agenda` para que cada bloque horario sea único por médico:

- `ALTER TABLE "Agenda" ADD PRIMARY KEY ("ID","Fecha","Hora");`

### (c) transacciones (3 etapas)
1. **Reserva de hora** – `c_reserva.sql` define la función `reservar_hora(run, id_medico, fecha, hora)` que corre dentro de una transacción, valida que el paciente exista, verifica que la hora siga en `Agenda`, evita duplicar atenciones con `Efectuada=false`, inserta la cita con `Diagnostico='Pendiente'` y elimina la disponibilidad consumida:
   ```sql
   begin;
       select reservar_hora('370333-9' formato de Run, 62, date '2025-11-15' la fecha , time '09:00' la hora);
   commit;
   ```
2. **Cancelación** – `c_cancelar.sql` define `cancelar_hora(id_atencion)`. La función bloquea la atención, verifica que no haya sido efectuada, comprueba que el bloque no exista ya en `Agenda`, reinsertar la fila en `Agenda` y elimina la atención pendiente:
   ```sql
   begin;
       select cancelar_hora(4879 id de una hora x);
   commit;
   ```
3. **Confirmación** – `c_confirmacion.sql` expone `confirmar_atencion(id_atencion)`, que bloquea la fila, valida que aún esté pendiente y actualiza `Efectuada=true`. Este paso deja la atención lista para que el SP/trigger posterior (inciso d/e) genere recetas y órdenes.

### (d) Stored procedure para recetas y órdenes
- `SPorden.sql` define `generar_documentos(id_atencion)`, que arma las recetas normales (`recetas/`), psicotrópicas (`recetas_psico/`) y la orden (`ordenes/`) usando únicamente datos existentes de la atención. La función escribe usando la variable GUC `uc.repo_base`; por defecto toma el directorio actual, pero puedes fijarlo antes de confirmar una atención (ejemplo: `SET uc.repo_base TO '/ruta/a/E4/';`).
