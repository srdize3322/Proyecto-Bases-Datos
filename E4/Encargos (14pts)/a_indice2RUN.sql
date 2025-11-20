-- indice secundario para run/rut en las tables pregunta 2-a
create index idx_persona_run on "Persona" ("RUN");
create index idx_institucion_salud_rut on "InstituciondeSalud" ("RUT");
