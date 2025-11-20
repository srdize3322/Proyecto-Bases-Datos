-- stored procedure para generar recetas y ordenes
-- importante: actualizar v_base_dir si la ruta del repo cambia
-- info de https://stackoverflow.com/questions/14275493/how-to-create-text-file-using-sql-script-with-text
create or replace function generar_documentos(p_id_atencion integer)
returns void
language plpgsql as $func$
declare
    v_base_dir text := nullif(current_setting('uc.repo_base', true), '');
    v_atencion   "Atencion"%rowtype;
    -- uso de rowtipe https://www.ibm.com/docs/es/db2/12.1.x?topic=plsql-rowtype-attribute-in-record-type-declarations
    -- para guardar una fila como una variable
    v_paciente   "Persona"%rowtype;
    v_medico     "Persona"%rowtype;
    v_fecha_txt  text;
    v_diag       text;
    v_tiene_normales boolean;
    v_tiene_psico    boolean;
    v_tiene_ordenes  boolean;
begin
    if v_base_dir is null then
        v_base_dir := '';
    elsif right(v_base_dir,1) <> '/' then
        v_base_dir := v_base_dir || '/';
    end if;
    select * into v_atencion from "Atencion" where "ID" = p_id_atencion;
    if not found then
        raise exception 'ERROR-ATENCION-NO-EXISTE';
    end if;

    select * into v_paciente from "Persona" where "ID" = v_atencion."IDPaciente";
    select * into v_medico   from "Persona" where "ID" = v_atencion."IDMedico";

    v_fecha_txt := to_char(v_atencion."fecha",'DD/MM/YYYY');
    v_diag      := coalesce(v_atencion."Diagnostico",'SIN DIAGNOSTICO');

    create temp table if not exists tmp_doc (ord int, linea text) on commit drop;

    select exists (
        select 1 from "medicamentos"
        where "IDAtencion" = p_id_atencion
          and coalesce("Psicotropico",false) = false
    ) into v_tiene_normales;

    select exists (
        select 1 from "medicamentos"
        where "IDAtencion" = p_id_atencion
          and coalesce("Psicotropico",false) = true
    ) into v_tiene_psico;

    select exists (
        select 1 from "Orden"
        where "IDAtencion" = p_id_atencion
    ) into v_tiene_ordenes;

    if v_tiene_normales then
        truncate tmp_doc;
        insert into tmp_doc values
            (1, 'Receta Medica Electronica'),
            (2, format('Paciente: %s %s RUN %s', coalesce(v_paciente."Nombres",''), coalesce(v_paciente."Apellidos",''), coalesce(v_paciente."RUN",''))),
            (3, format('Diagnostico: %s', v_diag)),
            (4, format('Fecha: %s', v_fecha_txt)),
            (5, '');

        insert into tmp_doc
        select 100 + row_number() over (), upper(m."Medicamento") || ' - ' || coalesce(m."Posologia",'')
        from "medicamentos" m
        where m."IDAtencion" = p_id_atencion
          and coalesce(m."Psicotropico",false) = false;

        insert into tmp_doc values
            (900,''),
            (901, format('Dr. %s %s', coalesce(v_medico."Nombres",''), coalesce(v_medico."Apellidos",''))),
            (902, format('RUN %s', coalesce(v_medico."RUN",'')));

        execute format(
            'COPY (SELECT linea FROM tmp_doc ORDER BY ord) TO %L',
            v_base_dir || format('recetas/receta_%s.txt', p_id_atencion)
        );
    end if;

    if v_tiene_psico then
        truncate tmp_doc;
        insert into tmp_doc values
            (1, 'Receta Medica Electronica Psicotropicos'),
            (2, format('Paciente: %s %s RUN %s', coalesce(v_paciente."Nombres",''), coalesce(v_paciente."Apellidos",''), coalesce(v_paciente."RUN",''))),
            (3, format('Diagnostico: %s', v_diag)),
            (4, format('Fecha: %s', v_fecha_txt)),
            (5, '');

        insert into tmp_doc
        select 100 + row_number() over (),
               '* ' || upper(m."Medicamento") || ' - ' || coalesce(m."Posologia",'')
        from "medicamentos" m
        where m."IDAtencion" = p_id_atencion
          and coalesce(m."Psicotropico",false) = true;

        insert into tmp_doc values
            (900,''),
            (901, format('Dr. %s %s', coalesce(v_medico."Nombres",''), coalesce(v_medico."Apellidos",''))),
            (902, format('RUN %s', coalesce(v_medico."RUN",'')));

        execute format(
            'COPY (SELECT linea FROM tmp_doc ORDER BY ord) TO %L',
            v_base_dir || format('recetas_psico/receta_psico_%s.txt', p_id_atencion)
        );
    end if;

    if v_tiene_ordenes then
        truncate tmp_doc;
        insert into tmp_doc values
            (1, 'Orden de examen'),
            (2, format('Paciente: %s %s RUN %s', coalesce(v_paciente."Nombres",''), coalesce(v_paciente."Apellidos",''), coalesce(v_paciente."RUN",''))),
            (3, format('Diagnostico: %s', v_diag)),
            (4, format('Fecha: %s', v_fecha_txt)),
            (5, format('Dr. %s %s', coalesce(v_medico."Nombres",''), coalesce(v_medico."Apellidos",''))),
            (6, format('RUN %s', coalesce(v_medico."RUN",''))),
            (7, '');

        insert into tmp_doc
        select 100 + row_number() over (),
               coalesce(a."Codigo"::text,'-') || ' ' || coalesce(a."ConsAtMedica",'')
        from "Orden" o
        join "Arancel" a on a."ID" = o."IDArancel"
        where o."IDAtencion" = p_id_atencion;

        execute format(
            'COPY (SELECT linea FROM tmp_doc ORDER BY ord) TO %L',
            v_base_dir || format('ordenes/orden_%s.txt', p_id_atencion)
        );
    end if;
end;
$func$;
