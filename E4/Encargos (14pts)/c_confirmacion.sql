-- funcion para marcar la atencion como efectuada 
create or replace function confirmar_atencion(p_id_atencion integer)
returns void
language plpgsql as $$
--defino variable
declare
    v_efectuada boolean;
begin
    select "Efectuada"
    into v_efectuada
    from "Atencion"
    where "ID" = p_id_atencion
    --bloqueo para actualizacion
    for update;
-- si no existe
    if not found then
        raise exception 'ERROR-ATENCION-NO-EXISTE';
    end if;
--si ya fue marcada como oK
    if v_efectuada then
        raise exception 'ERROR-ATENCION-YA-EFECTUADA';
    end if;
--Cambiamos valor
    update "Atencion"
    set "Efectuada" = true
    where "ID" = p_id_atencion
      and not "Efectuada";
-- si no se efectuo levanto error denuevo
    if not found then
        raise exception 'ERROR-NO-SE-ACTUALIZO-ATENCION';
    end if;
end;
$$;
