-- p_ruta_base debe ser la ruta absoluta al directorio del proyecto
--   rowtype: https://www.ibm.com/docs/es/db2/12.1.x?topic=plsql-rowtype-attribute-in-record-type-declarations
--   copy a txt: https://stackoverflow.com/questions/14275493/how-to-create-text-file-using-sql-script-with-text
-- loop https://stackoverflow.com/questions/6069024/syntax-of-for-loop-in-sql-server
-- commit on drop https://stackoverflow.com/questions/52384350/postgres-create-temp-table-with-select-and-on-commit-drop

create or replace function generar_documentos(p_id_atencion integer, p_ruta_base text)
returns void
language plpgsql as $func$
declare
    -- aca usamos coalesce(p_ruta_base, '')
    -- coalesce devuelve primero que no es null
    -- en este caso si p_ruta_base viene null usamos '' poque no puede ser null..
    v_base_dir text := coalesce(p_ruta_base, '');
    -- rowtype hace que esta variable tenga exactamente la misma estructura que la tabla "Atencion" y podemos llamar sus atributos (link)

    v_atencion "Atencion"%rowtype;
    -- repetimos rowtipe aqui
    v_paciente "Persona"%rowtype;
    v_medico "Persona"%rowtype;
    v_fecha_txt text;
    v_diag text;
    v_paciente_nombre text;
    v_paciente_apellido text;
    v_paciente_run text;
    v_medico_nombre text;
    v_medico_apellido text;
    v_medico_run text;
    v_tiene_normales boolean;
    v_tiene_psico boolean;
    v_tiene_ordenes boolean;
    -- v_ord la usamos como numero de linea ordenado, asi luego solo ordenamos por este campo
    v_ord integer;
    v_linea text;
    -- estos record son variables genericas que se van llenando dentro de los loops
    rec_med record;
    rec_ord record;
begin
    -- normalizamos la ruta base si no llega nada -> error, y si llega sin slash final se lo agregamos ya tuve errores por eso
    if v_base_dir = '' then
    -- error si no hay nada
        raise exception 'ERROR-RUTA-BASE-NO-ENTREGADA';
    end if;
    -- si no termino en /
    if right(v_base_dir,1) <> '/' then
    --Entonces lo agrego
        v_base_dir := v_base_dir || '/';
    end if;

    -- obtenemos la atencion completa para saber paciente, medico y diagnostico
    select * into v_atencion from "Atencion" where "ID" = p_id_atencion;
    -- error si la atencion no existe
    if not found then
        raise exception 'ERROR-ATENCION-NO-EXISTE';
    end if;

    -- porrowtype guardamos las variables..
    select * into v_paciente from "Persona" where "ID" = v_atencion."IDPaciente";
    select * into v_medico from "Persona" where "ID" = v_atencion."IDMedico";

    -- armamos datos que se reutilizan en todos los documentos
    v_fecha_txt := to_char(v_atencion."fecha",'DD/MM/YYYY');

    -- aca usamos de nuevo coalesce para marcar sin diagnostico
    v_diag := coalesce(v_atencion."Diagnostico",'SIN DIAGNOSTICO');

    -- estos coalesce son la misma idea: si algo viene null lo reemplazamos por texto vacio
    v_paciente_nombre := coalesce(v_paciente."Nombres",'NOHAYNOMBRE');
    v_paciente_apellido := coalesce(v_paciente."Apellidos",'NOHAYAPELLIDO');
    v_paciente_run := coalesce(v_paciente."RUN",'NOHAYRUN');
    v_medico_nombre := coalesce(v_medico."Nombres",'NOMEDICO');
    v_medico_apellido := coalesce(v_medico."Apellidos",'APELLIDOMEDICO');
    v_medico_run := coalesce(v_medico."RUN",'RUNMEDICO');

    -- me saco una tabla temporal para buffer de lineas antes del copy a archivo
    -- commit drop hace que esta tabla se borre sola al terminar la transaccion
    create temp table if not exists tmp_doc (ord int, linea text) on commit drop;

    -- determinamos si existen no psicotropicos)
    -- exists devuelve true si la subconsulta tiene al menos una fila
    select exists (
        --select 1 es como decir where exists es mas eficioente por lo que lei
        select 1 from "medicamentos"
        where "IDAtencion" = p_id_atencion
          and coalesce("Psicotropico",false) = false
    ) into v_tiene_normales;

    -- ahora revisamos si hay medicamentos psicotropicos
    select exists (
        select 1 from "medicamentos"
        where "IDAtencion" = p_id_atencion
          and coalesce("Psicotropico",false) = true
    ) into v_tiene_psico;

    -- y aca vemos si hay alguna orden asociada a la atencion
    select exists (
        select 1 from "Orden"
        where "IDAtencion" = p_id_atencion
    ) into v_tiene_ordenes;

    if v_tiene_normales then
        -- inicio el contador en 0..
        v_ord := 0;
        -- por si a caso elimino todo en tmp_doc con truncate
        -- la logica es ir sumando este numero arbitrario para luego hacerle un orden by y quede todo en orden en el que lo escribi aqui
        truncate tmp_doc;
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, 'receta medica electronica');
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('paciente: %s %s run %s', v_paciente_nombre, v_paciente_apellido, v_paciente_run));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('diagnostico: %s', v_diag));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('fecha: %s', v_fecha_txt));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, '');

        -- listado de medicamentos normales
        -- este loop es del tipo "for in select... loop sobre los medicamentos
        -- por cada fila del select se llena rec_med con las columnas y se ejecuta el bloque hasta end loop
        for rec_med in
            select upper("Medicamento") as nombre, coalesce("Posologia",'') as posologia
            from "medicamentos"
            where "IDAtencion" = p_id_atencion
            -- devo usar cualese porque si uso false = null es null
              and coalesce("Psicotropico",false) = false
-- loop para revisar si hay mas de un resultado- med
        loop
            v_ord := v_ord + 10;
            v_linea := rec_med.nombre || ' - ' || rec_med.posologia;
            insert into tmp_doc values (v_ord, v_linea);
        end loop;

        -- firma del medico al final del documento
        -- continuo la misma logia ce antes pero para este documento
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, '');
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('dr. %s %s', v_medico_nombre, v_medico_apellido));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('run %s', v_medico_run));

        -- escribimos el archivo receta/receta_ID.txt usando copy es decir ejecuto el comando de copi segun de tmp por orden de ord para que quede con el mismo orden
        -- el copy toma el resultado del select y lo deja en el archivo de texto indicado
        execute format(
            'copy (select linea from tmp_doc order by ord) to %L',
            v_base_dir || format('recetas/receta_%s.txt', p_id_atencion)
        );
    end if;





-- para los psicotropicos (aplico misma logica)

    if v_tiene_psico then
        -- cabecera receta psicotropica
        v_ord := 0;
        truncate tmp_doc;
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, 'receta medica electronica psicotropicos');
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('paciente: %s %s run %s', v_paciente_nombre, v_paciente_apellido, v_paciente_run));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('diagnostico: %s', v_diag));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('fecha: %s', v_fecha_txt));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, '');

        -- medicamentos psicotropicos resaltados con *
        -- mismo tipo de loop que antes, pero ahora solo trae los psicotropicos
        for rec_med in
            select upper("Medicamento") as nombre, coalesce("Posologia",'') as posologia
            from "medicamentos"
            where "IDAtencion" = p_id_atencion
              and coalesce("Psicotropico",false) = true
        loop
            v_ord := v_ord + 10;
            v_linea := '* ' || rec_med.nombre || ' - ' || rec_med.posologia;
            insert into tmp_doc values (v_ord, v_linea);
        end loop;

        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, '');
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('dr. %s %s', v_medico_nombre, v_medico_apellido));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('run %s', v_medico_run));

        -- escribimos la version psicotropica en el subdirectorio recetas_psico/
        execute format(
            'copy (select linea from tmp_doc order by ord) to %L',
            v_base_dir || format('recetas_psico/receta_psico_%s.txt', p_id_atencion)
        );
    end if;







-- para orden de examen misma logica

    if v_tiene_ordenes then
        -- cabecera orden de examenes
        v_ord := 0;
        truncate tmp_doc;
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, 'orden de examen');
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('paciente: %s %s run %s', v_paciente_nombre, v_paciente_apellido, v_paciente_run));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('diagnostico: %s', v_diag));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('fecha: %s', v_fecha_txt));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('dr. %s %s', v_medico_nombre, v_medico_apellido));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, format('run %s', v_medico_run));
        v_ord := v_ord + 10;
        insert into tmp_doc values (v_ord, '');

        -- listado de ordenes / examenes
        -- este loop recorre todas las ordenes ligadas a la atencion
        -- y arma una linea con codigo y nombre del examen
        for rec_ord in
            select coalesce(a."Codigo"::text,'-') as codigo,
                   coalesce(a."ConsAtMedica",'') as nombre
            from "Orden" o
            join "Arancel" a on a."ID" = o."IDArancel"
            where o."IDAtencion" = p_id_atencion
        loop
            v_ord := v_ord + 10;
            v_linea := rec_ord.codigo || ' ' || rec_ord.nombre;
            insert into tmp_doc values (v_ord, v_linea);
        end loop;

        -- escribimos la orden en ordenes/orden_ID.txt
        execute format(
            'copy (select linea from tmp_doc order by ord) to %L',
            v_base_dir || format('ordenes/orden_%s.txt', p_id_atencion)
        );
    end if;


-- termino proceso SP

end;
$func$;