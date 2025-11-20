-- creo la PK para agenda, respalda la pregunta B
alter table "Agenda"
    add primary key ("ID", "Fecha", "Hora");
