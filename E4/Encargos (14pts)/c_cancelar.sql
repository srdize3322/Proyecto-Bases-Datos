-- funcion para cancelar y devolver la hora a agenda
create or replace function cancelar_hora(p_id_atencion integer)
returns void
language plpgsql as $$
--variales internas
declare
    v_id_paciente integer;
    v_id_medico integer;
    v_fecha  date;
    v_hora  time without time zone;
    v_efectuada boolean;
begin
    -- obtengo y bloqueo la atencion objetivo
    select "IDPaciente","IDMedico","fecha","hora","Efectuada"
    --guardo valores en las variables
    into v_id_paciente,v_id_medico,v_fecha,v_hora,v_efectuada
    from "Atencion"
    where "ID" = p_id_atencion
    --bloqueo la fila
    for update;

    if not found then
        raise exception 'ERROR-ATENCION-NO-EXISTE';
    end if;
--si tengo la atencion efectuada no puedo cancelarla
    if v_efectuada then
        raise exception 'ERROR-ATENCION-YA-EFECTUADA';
    end if;

    -- evitar duplicar el slot en agenda si alguien lo manipuló mal
    if exists (
        select 1
        -- seleccionar una fila que cumpla el criterio https://stackoverflow.com/questions/40667556/what-does-select-1-do/54419532
        from "Agenda"
        where "ID" = v_id_medico
          and "Fecha" = v_fecha
          and "Hora" = v_hora
    ) then
        raise exception 'ERROR-HORA-YA-EXISTE-EN-AGENDA';
    end if;

    -- devuelvo la hora al las disponible
    insert into "Agenda" ("ID","Fecha","Hora")
    values (v_id_medico, v_fecha, v_hora);
--reparo lo quecambie para eliminarlo
    delete from "Atencion"
    where "ID" = p_id_atencion;

    if not found then
        raise exception 'ERROR-NO-SE-ELIMINO-ATENCION';
    end if;
end;
$$;
