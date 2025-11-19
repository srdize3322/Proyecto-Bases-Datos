# Contenidos revisados IIC2413 2025-2

## Administración, motivación y operación del curso
### Clase 01a - Aspectos Administrativos
- Se enumeran profesores, ayudantes (coordinaciones y bienestar) y el repositorio como canal oficial para avisos y material.
- Horarios de cátedra, talleres y ayudantías incluyen exigencia de asistencia mínima (70 %) y aviso de posibles cambios de sala.
- Esquema de evaluación: dos interrogaciones, examen con eximición, talleres, cuatro entregas del proyecto y ponderaciones específicas para nota cátedra/proyecto.
- Políticas de integridad académica (sin IA generativa, arrepentimiento temprano) y procedimientos para justificar inasistencias, atraso máximo y fórmula final.

### Clase 01b - Motivación y modelos de datos
- Contextualiza el rol de las bases de datos en actividades cotidianas (pagos, RRSS, Uber) y muestra que cualquier rol profesional requiere interactuar con datos.
- Define DBMS, sus ventajas frente a scripts ad-hoc y repasa actores históricos (Codd, Chamberlin, Boyce).
- Recorre modelos estructurados y semiestructurados (relacional, jerárquico, XML, key-value) y cuándo conviene cada uno.
- Enumera motores actuales (PostgreSQL, MySQL, Oracle, MongoDB, Neo4J, Cassandra) y destaca que el curso cubre SQL, modelado y nociones NoSQL.

### Ayudantía 1 - Servidor Linux y CLI
- Introduce conceptos de servidor físico/virtual, direcciones IP, DNS y puertos comunes (SSH, HTTP, HTTPS) para comprender la infraestructura del proyecto.
- Practica conexión vía `ssh` y transferencia con `scp` hacia stonebraker, junto a comandos básicos (`touch`, `mkdir`, `rm`, `nano`, `mv`).
- Recalca que las entregas se revisan directamente en el servidor institucional y da un anexo de comandos útiles (pwd, ls, cd, php, etc.).

## Fundamentos del modelo relacional y diseño (SQL)
### Clase 01c - Introducción al modelo relacional
- Diferencia esquema, instancia y dominio; define relación, atributo y tupla formalmente y usa notación Películas(id, nombre, año,...).
- Explica dominio/tipos, llaves (super, candidatas, primarias) y la motivación de restricciones como unicidad e integridad referencial.

### Clase 02 - Álgebra relacional
- Presenta operadores básicos (proyección, selección, unión, producto, join, intersección, diferencia) con ejemplos sobre actores/películas.
- Introduce árboles de expresión, reglas de reescritura y consultas monotónicas vs no monotónicas (necesidad de diferencia para “Nolan y no Iñárritu”).

### Clase 03 - Diseño de bases de datos I
- Recorre el proceso análisis → diseño conceptual (E/R) → diseño lógico, definiendo entidades, atributos, llaves y relaciones binarias/múltiples.
- Cubre multiplicidades, notaciones, jerarquías (ISA), entidades débiles y agregación con ejemplos (áreas/locaciones, vinos vs cervezas).

### Clase 04 - Diseño de bases de datos II
- Profundiza en llaves (super, candidatas, primarias, surrogate) y consecuencias de definirlas mal.
- Explica el paso E/R → relacional: creación de tablas para entidades fuertes/débiles, manejo de relaciones N:M y 1:N, claves foráneas y restricciones.
- Repasa participación total/parcial, herencia, opciones de modelado y la sintaxis SQL correspondiente (CREATE TABLE con PK/FK).

### Clase 05 - Dependencias y formas normales
- Justifica la eliminación de redundancia mostrando anomalías (inserción, actualización, eliminación) en tablas no normalizadas.
- Define dependencia funcional, reglas de inferencia (obs 1-6), cálculo de clausuras y descomposición preservando dependencias.
- Describe 1NF, 2NF, 3NF y BCNF con ejemplos prácticos, destacando cuándo cada restricción evita redundancia.

### Ayudantía 2 - Modelo E/R
- Refuerza sintaxis E/R (entidades, atributos, llaves primarias/parciales, relaciones) y multiplicidades típicas.
- Incluye ejemplos de relaciones binarias, entidades débiles, jerarquías y un ejercicio completo (DCCanvas) para pasar a esquema relacional.

### Ayudantía 3 - Formas normales
- Repasa tipos de llaves, dependencias funcionales y criterios de 1NF/2NF/3NF/BCNF con ejemplos numéricos y tablas.
- Muestra descomposición paso a paso para eliminar dependencias parciales y transitivas.

### Ayudantía 4 - Apoyo Entrega 1
- Analiza los archivos de muestra de DCColita de Rana (aranceles, planes, personas, recetas) para inferir entidades y atributos relevantes.
- Enumera reglas de negocio (roles de persona, afiliación previsional, recetas ligadas a maestros, órdenes ligadas a procedimientos) como base del E/R.

### Ayudantía Repaso I1
- Integra Álgebra Relacional, paso de E/R a tablas, SQL básico (SELECT/WHERE/JOIN/AGG) y normalización como preparación para I1.

## SQL y manipulación de datos
### Clase 06 y 07 - SQL completo
- Clasifica sublenguajes (DDL, DML, DCL, DQL, TCL), tipos de datos y sentencia CREATE/ALTER/DROP con restricciones.
- Cubre INSERT, UPDATE, DELETE, SELECT con filtros, alias, JOINs explícitos, ORDER BY, funciones agregadas, GROUP BY/HAVING y subconsultas.
- Explica manejo de NULL, operadores de conjuntos, y efectos de olvidar WHERE en UPDATE/DELETE, junto a acciones sobre FK (CASCADE, SET NULL).

### Ayudantía 5 - Práctica SQL (actor/película)
- Provee CSVs (actor, películas, actuo_en) y scripts para poblar tablas, más un ERD, para ejercitar carga y consultas SQL.

### Ayudantía 6 - Guía SQL (jugadores)
- Notebook que crea el esquema futbolístico (jugadores, ligas, clubes, nacionalidades, posiciones) y practica DDL/DML.
- Plantea consultas TOP-N (jugadores, clubes, días, meses), agregados y ranking por precio/edad, obligando a usar GROUP BY y ORDER BY.

## Lógica en la base de datos, vistas y procedimientos
### Clase 08 - Lógica en la BD
- Presenta vistas (virtuales/materializadas), su reescritura y casos de uso (agregar lecturas reutilizables, caching controlado).
- Introduce triggers (BEFORE/AFTER, ROW/STATEMENT) para forzar reglas como stock decreciente o refresco de vistas materializadas.
- Recorre PL/pgSQL: sintaxis de funciones, variables, control de flujo (IF, LOOP), RETURN y su utilidad como stored procedures.

### Ayudantía 11 - Repaso I2
- Practica stored procedures que devuelven tablas y triggers que generan estructuras auxiliares (ejercicios basados en listas de precios y productos).
- Refuerza conceptos de vistas, manejo de vigencias y automatización de reglas para preparar la interrogación 2.

## Transacciones, locks, arquitectura y recuperación
### Clase 09 - ACID, transacciones y locks
- Define transacciones, propiedades ACID y ejemplos de fallas (dirty reads, lost updates, phantoms).
- Explica serialización vs ejecución concurrente, pruebas de conflict-serializability y el protocolo Strict 2PL (shared/exclusive locks, problemas de deadlock y long transactions).
- Muestra comandos SQL: BEGIN/START TRANSACTION, COMMIT, ROLLBACK, SAVEPOINT, niveles de aislamiento (READ UNCOMMITTED → SERIALIZABLE) y granularidad de locks.

### Clase 10 - Arquitectura y recuperación de fallas
- Describe jerarquía de memoria, páginas, buffers, RAID, diferencias entre disco y SSD y cómo el DBMS mueve páginas.
- Introduce logging (undo, redo, undo/redo), write-ahead logging, formatos de log, reglas de escritura y recuperaciones tras falla.
- Explica checkpoints (quiescent y non-quiescent), detección de fallas en el log y procedimientos para truncar/replay.

## Índices y evaluación de consultas
### Clase 11 - Fundamentos de EDD e índices
- Define modelo de costo en I/O, diferencia memoria principal/secundaria y repasa estructuras básicas (arreglos, listas, hash).
- Presenta hash indexes (buckets, overflow, caso ideal vs peor) y B+ trees (estructura, altura, búsqueda exacta/rango, enlaces de hojas).
- Discute índices cluster/uncluster y cuándo usar hash vs B+ (igualdad vs rango).

### Clase 12 - Algoritmos y evaluación de consultas
- Introduce el modelo iterador (open/next/close) y cómo las operaciones leen páginas desde buffer.
- Analiza operadores lineales, nested-loop join, block nested loop, external merge sort y costos en páginas para cada estrategia.

## NoSQL, búsqueda y privacidad
### Clase 13 - NoSQL, MongoDB y full-text search
- Clasifica modelos NoSQL (key-value, grafos, documentos), detalla JSON y cómo MongoDB almacena colecciones flexibles.
- Resume comandos `db.collection.find()` con filtros/proyecciones, consideraciones BASE (consistencia eventual) y uso como cache de una BD relacional.
- Explica índices invertidos, creación de índices `text` en MongoDB y ranking con TF-IDF.

### Clase 14 - Privacidad
- Motiva riesgos (Netflix Prize, gobernador de MA) y diferencia seguridad vs privacidad.
- Cubre técnicas de anonimización (k-anonimato, l-diversidad), datos sintéticos vs respuestas agregadas y trade-off utilidad/privacidad.
- Introduce privacidad diferencial: mecanismos de Laplace, parámetro ε, sensibilidad, composición y por qué previene ataques de asociación.

## Herramientas de proyecto, PHP y web
### Ayudantía 8 - PHP desde cero
- Revisa conceptos básicos del lenguaje (sintaxis, variables, operadores lógicos, iteraciones, listas, strings, funciones, lectura/escritura de archivos).
- Incluye actividades: leer CSV, eliminar duplicados, escribir archivos limpios y mostrar listas, preparando scripts de limpieza.

### Ayudantía 10 - Apoyo E3 (limpieza de datos)
- Define objetivo de la entrega 3: limpiar CSV con PHP antes de cargar a PostgreSQL, priorizando corrección → NULL → eliminación.
- Lista errores comunes (datos duplicados, tipos, formatos, incoherencias) y cómo solucionarlos (PHP vs SQL) con logging `archivoLOG.txt` y `cargaERR.csv`.
- Describe esquema esperado, carga via `carga.sql` y contenido del informe README.

### Ayudantía 12 - Web + PHP + SQL
- Explica qué es un servidor web, métodos HTTP (GET/POST) y cómo se combinan HTML+CSS con PHP bajo el patrón MVC.
- Detalla estructura de un formulario de login, cómo alojar archivos PHP (`php -S`, Sites en stonebraker) y cómo conectar a PostgreSQL con PDO (manejo de errores, consultas preparadas).
- Muestra cómo ejecutar queries desde PHP y presentar resultados en la vista.

## Material pendiente por limitaciones de acceso
- Ayudantía 9 (PDF generado en Canva) no contiene texto extraíble; en este entorno de solo lectura no fue posible correr OCR, por lo que debe revisarse manualmente para incorporar sus contenidos.
