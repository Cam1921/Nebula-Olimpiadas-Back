

-----crear un registro de una olimpiada------------------------------
INSERT INTO olimpiada
    (nombre_olimpiada, gestion, fecha_inicio, fecha_fin, created_at, updated_at)
VALUES
    ('Olimpiada Científica 2026', 2026, '2026-10-10', '2026-10-15', NOW(), NOW());

----------insercion de datos de area, grado, nivel----------------------

INSERT INTO grado
    (nombre_grado, created_at, updated_at)
VALUES
    ('1ro Primaria', NOW(), NOW()),
    ('2ro Primaria', NOW(), NOW()),
    ('3ro Primaria', NOW(), NOW()),
    ('4to Primaria', NOW(), NOW()),
    ('5to Primaria', NOW(), NOW()),
    ('6to Primaria', NOW(), NOW()),
    ('1ro Secundaria', NOW(), NOW()),
    ('2do Secundaria', NOW(), NOW()),
    ('3ro Secundaria', NOW(), NOW()),
    ('4to Secundaria', NOW(), NOW()),
    ('5to Secundaria', NOW(), NOW()),
    ('6to Secundaria', NOW(), NOW());

INSERT INTO area
    (nombre_area, created_at, updated_at)
VALUES
    ('Informatica', NOW(), NOW()),
    ('Astrofisica', NOW(), NOW()),
    ('Biologia', NOW(), NOW()),
    ('Matematicas', NOW(), NOW()),
    ('Quimica', NOW(), NOW()),
    ('Robotica', NOW(), NOW());

INSERT INTO nivel
    (nombre_nivel, created_at, updated_at)
VALUES
    ('Guacamayo', NOW(), NOW()),
    ('Bufeo', NOW(), NOW()),
    ('Puma', NOW(), NOW()),
    ('Primer nivel', NOW(), NOW()),
    ('Segundo nivel', NOW(), NOW()),
    ('Tercer nivel', NOW(), NOW()),
    ('Cuarto nivel', NOW(), NOW()),
    ('3P', NOW(), NOW()),
    ('4P', NOW(), NOW()),
    ('5P', NOW(), NOW()),
    ('6P', NOW(), NOW()),
    ('1S', NOW(), NOW()),
    ('2S', NOW(), NOW()),
    ('3S', NOW(), NOW()),
    ('4S', NOW(), NOW()),
    ('5S', NOW(), NOW()),
    ('6S', NOW(), NOW()),
    ('Builders S', NOW(), NOW()),
    ('Lego P', NOW(), NOW()),
    ('Lego S', NOW(), NOW()),
    ('Builders P', NOW(), NOW());




INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON (
    (g.nombre_grado = '3ro Primaria' AND n.nombre_nivel = '3P') OR
        (g.nombre_grado = '4to Primaria' AND n.nombre_nivel = '4P') OR
        (g.nombre_grado = '5to Primaria' AND n.nombre_nivel = '5P') OR
        (g.nombre_grado = '6to Primaria' AND n.nombre_nivel = '6P') OR
        (g.nombre_grado = '1ro Secundaria' AND n.nombre_nivel = '1S') OR
        (g.nombre_grado = '2do Secundaria' AND n.nombre_nivel = '2S') OR
        (g.nombre_grado = '3ro Secundaria' AND n.nombre_nivel = '3S') OR
        (g.nombre_grado = '4to Secundaria' AND n.nombre_nivel = '4S') OR
        (g.nombre_grado = '5to Secundaria' AND n.nombre_nivel = '5S') OR
        (g.nombre_grado = '6to Secundaria' AND n.nombre_nivel = '6S')
);

-----Informatica------------------------------

INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Guacamayo'
WHERE g.nombre_grado IN ('5to Primaria','6to Primaria');

-- Bufeo (Primero a 3ro Secundaria)
INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Bufeo'
WHERE g.nombre_grado IN ('1ro Secundaria','2do Secundaria','3ro Secundaria');

-- Puma (Cuarto a 6to Secundaria)
INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Puma'
WHERE g.nombre_grado IN ('4to Secundaria','5to Secundaria','6to Secundaria');

--Matematicas---------------------------------------------------

INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Primer nivel'
WHERE g.nombre_grado IN ('1ro Secundaria','2do Secundaria');

-- Segundo nivel (Tercero y 4to Secundaria)
INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Segundo nivel'
WHERE g.nombre_grado IN ('3ro Secundaria','4to Secundaria');

-- Tercer nivel (5to Secundaria)
INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Tercer nivel'
WHERE g.nombre_grado = '5to Secundaria';

-- Cuarto nivel (6to Secundaria)
INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Cuarto nivel'
WHERE g.nombre_grado = '6to Secundaria';

INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Builders P'
WHERE g.nombre_grado IN ('5to Primaria','6to Primaria');

INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Builders S'
WHERE g.nombre_grado IN ('1ro Secundaria','2do Secundaria','3ro Secundaria',
    '4to Secundaria','5to Secundaria','6to Secundaria');


INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Lego P'
WHERE g.nombre_grado IN ('5to Primaria','6to Primaria');

INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Lego S'
WHERE g.nombre_grado IN ('1ro Secundaria','2do Secundaria','3ro Secundaria',
    '4to Secundaria','5to Secundaria','6to Secundaria');

---Vinculación por area--------------------
INSERT INTO area_nivel
    (id_area, id_nivel, id_olimpiada, created_at, updated_at)
SELECT a.id, n.id, 1, NOW(), NOW()
FROM area a
    JOIN nivel n ON n.nombre_nivel IN (
    '3P','4P','5P','6P',
    '1S','2S','3S',
    '4S','5S','6S'
)
WHERE a.nombre_area = 'Astrofisica';

INSERT INTO area_nivel
    (id_area, id_nivel, id_olimpiada, created_at, updated_at)
SELECT a.id, n.id, 1, NOW(), NOW()
FROM area a
    JOIN nivel n ON n.nombre_nivel IN (
    '2S','3S','4S',
    '5S','6S'
)
WHERE a.nombre_area = 'Quimica';

INSERT INTO area_nivel
    (id_area, id_nivel, id_olimpiada, created_at, updated_at)
SELECT a.id, n.id, 1, NOW(), NOW()
FROM area a
    JOIN nivel n
    ON n.nombre_nivel IN ('2S','3S','4S','5S','6S')
WHERE a.nombre_area = 'Biologia';

INSERT INTO area_nivel
    (id_area, id_nivel, id_olimpiada, created_at, updated_at)
SELECT a.id, n.id, 1, NOW(), NOW()
FROM area a
    JOIN nivel n ON n.nombre_nivel IN ('Guacamayo','Bufeo','Puma')
WHERE a.nombre_area = 'Informatica';

INSERT INTO area_nivel
    (id_area, id_nivel, id_olimpiada, created_at, updated_at)
SELECT a.id, n.id, 1, NOW(), NOW()
FROM area a
    JOIN nivel n ON n.nombre_nivel IN ('Primer nivel','Segundo nivel','Tercer nivel','Cuarto nivel')
WHERE a.nombre_area = 'Matematicas';

-------triggert para crear el log de cambios-----------------------------------------

CREATE TABLE competidor_auditoria
(
    id SERIAL PRIMARY KEY,
    competidor_id INT,
    accion VARCHAR(10),
    -- INSERT, UPDATE, DELETE
    datos JSONB,
    created_at TIMESTAMP DEFAULT now()
);

INSERT INTO area_nivel
    (id_area, id_nivel, id_olimpiada, created_at, updated_at)
SELECT a.id, n.id, 1, NOW(), NOW()
FROM area a
    JOIN nivel n ON n.nombre_nivel IN (
    'Builders P','Builders S','Lego P',
    'Lego S'
)
WHERE a.nombre_area = 'Robotica';

CREATE TABLE evaluacion_auditoria
(
    id BIGSERIAL PRIMARY KEY,
    id_evaluacion BIGINT NOT NULL,
    evaluador_id BIGINT NOT NULL,
    cambios JSONB NOT NULL,
    ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT now()
);


CREATE OR REPLACE FUNCTION fn_log_competidor
()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
    INSERT INTO competidor_auditoria
        (competidor_id, accion, datos)
    VALUES
        (NEW.id, TG_OP, row_to_json(NEW));
    RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
    INSERT INTO competidor_auditoria
        (competidor_id, accion, datos)
    VALUES
        (NEW.id, TG_OP, row_to_json(NEW));
    RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
    INSERT INTO competidor_auditoria
        (competidor_id, accion, datos)
    VALUES
        (OLD.id, TG_OP, row_to_json(OLD));
    RETURN OLD;
END
IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;


CREATE TRIGGER trg_competidor_auditoria
AFTER
INSERT OR
UPDATE OR DELETE ON competidor
FOR EACH ROW
EXECUTE FUNCTION fn_log_competidor
();



ALTER TABLE evaluacion 
ADD CONSTRAINT unique_inscripcion_fase 
UNIQUE (id_inscripcion, id_fase);


CREATE OR REPLACE FUNCTION fn_migrar_inscritos_a_evaluaciones
()
RETURNS TRIGGER AS $$
BEGIN
    -- 🧩 Verificar si el estado de la fase cambió a 'abierta'
    IF NEW.estado = 'abierta' AND OLD.estado <> 'abierta' THEN

    /*----------------------------------------------------------
          Inserta en la tabla 'evaluacion' una fila por cada inscripción
          activa, vinculando la inscripción (i.id) con la fase abierta (NEW.id).
          Se incluyen timestamps para auditoría.
        ----------------------------------------------------------*/
    INSERT INTO evaluacion
        (id_inscripcion, id_fase, created_at, updated_at)
    SELECT i.id, NEW.id, NOW(), NOW()
    FROM inscripcion i
    WHERE NOT EXISTS (
            SELECT 1
    FROM evaluacion e
    WHERE e.id_inscripcion = i.id
        AND e.id_fase = NEW.id
        );
END
IF;

    -- Retornar el nuevo registro de la fase para completar el trigger
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE TRIGGER trg_migrar_inscritos
AFTER
UPDATE ON fase
FOR EACH ROW
EXECUTE FUNCTION fn_migrar_inscritos_a_evaluaciones
();

CREATE EXTENSION
IF NOT EXISTS unaccent;

insert into rol
    (nombre)
values
    ('administrador');
insert into rol
    (nombre)
values
    ('responsable');
insert into rol
    (nombre)
values
    ('evaluador');

--Fase clasificatorio
INSERT INTO fase
    (nombre, descripcion, estado, fecha_inicio, fecha_fin, created_at, updated_at)
VALUES
    (
        'Clasificación',
        'Fase inicial de evaluación y clasificación de proyectos.',
        'en proceso',
        NOW(),
        NOW(),
        NOW(),
        NOW()
);
--fase final
INSERT INTO fase
    (nombre, descripcion, estado, fecha_inicio, fecha_fin, created_at, updated_at)
VALUES
    (
        'Final',
        'Fase dode la olimpiada concluye y se premia.',
        'en proceso',
        NOW(),
        NOW(),
        NOW(),
        NOW()
);
--fase inscripcion
INSERT INTO fase
    (nombre, descripcion, estado,fecha_inicio, fecha_fin, created_at, updated_at)
VALUES
    (
        'Inscripcion',
        'Fase dode la olimpiada concluye y se premia.',
        'en proceso',
        NOW(),
        NOW(),
        NOW(),
        NOW()
);

-- 1️⃣ Primero creamos la función
CREATE OR REPLACE FUNCTION public.fn_migrar_inscripcion_a_evaluacion
()
RETURNS trigger AS $$
BEGIN
    -- Inserta una evaluación pendiente para la nueva inscripción
    INSERT INTO public.evaluacion
        (
        nota,
        descripcion,
        estado,
        respeto,
        integridad,
        puntualidad,
        id_inscripcion,
        id_fase,
        created_at,
        updated_at
        )
    VALUES
        (
            NULL,
            NULL,
            'pendiente',
            FALSE,
            FALSE,
            FALSE,
            NEW.id,
            1, -- puedes cambiar este número si deseas usar otra fase actual
            NOW(),
            NOW()
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 2️⃣ Luego creamos el trigger
DROP TRIGGER IF EXISTS trg_inscripcion_a_evaluacion
ON public.inscripcion;

CREATE TRIGGER trg_inscripcion_a_evaluacion
AFTER
INSERT ON public.
inscripcion
FOR
EACH
ROW
EXECUTE
FUNCTION public.fn_migrar_inscripcion_a_evaluacion
();


INSERT INTO area_nivel_fase
    (id_area_nivel, id_fase)
SELECT an.id, f.id
FROM area_nivel an
CROSS JOIN fase f
WHERE f.nombre = 'Clasificación';


INSERT INTO actividad
    (nombre, id_fase, created_at, updated_at)
VALUES
    (
        'calificacion fase c',
        1,
        NOW(),
        NOW()
);


INSERT INTO config_medallero
    (
    id_area_nivel,
    oros,
    platas,
    bronces,
    menciones_honorificas,
    created_at,
    updated_at
    )
SELECT
    id, -- id del registro en area_nivel
    0, -- oros
    0, -- platas
    0, -- bronces
    0, -- menciones_honorificas
    NOW(), -- created_at
    NOW()
-- updated_at
FROM area_nivel;




INSERT INTO actividad
    (nombre, id_fase, created_at, updated_at)
VALUES
    (
        'Calificacion de competidores',
        1,
        NOW(),
        NOW()
);
INSERT INTO actividad
    (nombre, id_fase, created_at, updated_at)
VALUES
    (
        'Calificacion de competidores',
        3,
        NOW(),
        NOW()
);
INSERT INTO actividad
    (nombre, id_fase, created_at, updated_at)
VALUES
    (
        'Publicacion de resultados',
        3,
        NOW(),
        NOW()
);
INSERT INTO actividad
    (nombre, id_fase, created_at, updated_at)
VALUES
    (
        'Publicacion de resultados',
        1,
        NOW(),
        NOW()
);
INSERT INTO actividad
    (nombre, id_fase, created_at, updated_at)
VALUES
    (
        'Premiacion',
        3,
        NOW(),
        NOW()
);
INSERT INTO actividad
    (nombre, id_fase, created_at, updated_at)
VALUES
    (
        'Asignar evaluadores',
        1,
        NOW(),
        NOW()
);
INSERT INTO actividad
    (nombre, id_fase, created_at, updated_at)
VALUES
    (
        'Importacion de competidores',
        2,
        NOW(),
        NOW()
)
