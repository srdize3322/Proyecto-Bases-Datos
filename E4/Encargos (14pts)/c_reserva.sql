-- primero creo la funcion parahacer la transaccion de reserva
create or replace function reservar_hora(
    p_run text, 
    p_medico integer,
    p_fecha date, p_hora time without time zone
) returns void
language plpgsql as $$
--defino
declare
    v_id_paciente integer;
    v_new_id integer;
begin
    -- bloquea la fila del paciente y valida que exista
    select "ID" into v_id_paciente
    from "Persona"
    where "RUN" = p_run
    --para bloquear que otro se meta al mimo tiempo lo saque de stackoverflow la explicacicon
    for update;

    if not found then
        raise exception 'ERROR-PACIENTE-NO-EXISTE', p_run;
    end if;

    -- asegura que la hora siga disponible en agenda de https://stackoverflow.com/questions/19423235/perform-vs-perform-1
    perform 1
    -- verifica que al menos 1 exista
    from "Agenda"
    where "ID" = p_medico
      and "Fecha" = p_fecha
      and "Hora" = p_hora
    for update;

    if not found then
        raise exception 'ERROR-HORA-NO-DISPONIBLE';
    end if;

    -- evita duplicar atenciones pendientes para ese bloque
    if exists (
        select 1
        from "Atencion"
        where "IDMedico" = p_medico
          and "fecha" = p_fecha
          and "hora" = p_hora
          and not "Efectuada"
    ) then
        raise exception 'ERROR-ATENCION-EXISTE-PARA-MEDICO';
    end if;


    -- genera el nuevo ID de atencion bloque tala
    lock table "Atencion" in share row exclusive mode;
    select coalesce(max("ID"), 0) + 1 into v_new_id from "Atencion";
    -- uso coalese para sacar el ultimo valor mas 1 que seria el nuevo
    insert into "Atencion" ("ID","fecha","IDPaciente","IDMedico","Diagnostico","Efectuada","hora")
    values (v_new_id, p_fecha, v_id_paciente, p_medico, 'Pendiente', false, p_hora);

    delete from "Agenda"
    where "ID" = p_medico
      and "Fecha" = p_fecha
      and "Hora" = p_hora;

    if not found then
        raise exception 'ERROR-NO-SE-ELIMINO-DE-AGENDA';
    end if;
end;
$$;
