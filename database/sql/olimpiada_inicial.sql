

-----crear un registro de una olimpiada------------------------------
INSERT INTO olimpiada
    (nombre_olimpiada, gestion, fecha_inicio, fecha_fin, created_at, updated_at)
VALUES
    ('Olimpiada Científica 2026', 2026, '2026-10-10', '2026-10-15', NOW(), NOW());

----------insercion de datos de area, grado, nivel----------------------

INSERT INTO grado
    (nombre_grado, created_at, updated_at)
VALUES
    ('Primero de primaria', NOW(), NOW()),
    ('Segundo de primaria', NOW(), NOW()),
    ('Tercero de primaria', NOW(), NOW()),
    ('Cuarto de primaria', NOW(), NOW()),
    ('Quinto de primaria', NOW(), NOW()),
    ('Sexto de primaria', NOW(), NOW()),
    ('Primero de secundaria', NOW(), NOW()),
    ('Segundo de secundaria', NOW(), NOW()),
    ('Tercero de secundaria', NOW(), NOW()),
    ('Cuarto de secundaria', NOW(), NOW()),
    ('Quinto de secundaria', NOW(), NOW()),
    ('Sexto de secundaria', NOW(), NOW());

INSERT INTO area
    (nombre_area, created_at, updated_at)
VALUES
    ('Informatica', NOW(), NOW()),
    ('Astrofisica', NOW(), NOW()),
    ('Biologia', NOW(), NOW()),
    ('Matematicas', NOW(), NOW()),
    ('Quimica', NOW(), NOW());

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
    ('Tercero de primaria', NOW(), NOW()),
    ('Cuarto de primaria', NOW(), NOW()),
    ('Quinto de primaria', NOW(), NOW()),
    ('Sexto de primaria', NOW(), NOW()),
    ('Primero de secundaria', NOW(), NOW()),
    ('Segundo de secundaria', NOW(), NOW()),
    ('Tercero de secundaria', NOW(), NOW()),
    ('Cuarto de secundaria', NOW(), NOW()),
    ('Quinto de secundaria', NOW(), NOW()),
    ('Sexto de secundaria', NOW(), NOW());




INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON g.nombre_grado = n.nombre_nivel
WHERE g.nombre_grado IN (
    'Tercero de primaria','Cuarto de primaria','Quinto de primaria','Sexto de primaria',
    'Primero de secundaria','Segundo de secundaria','Tercero de secundaria',
    'Cuarto de secundaria','Quinto de secundaria','Sexto de secundaria'
);

-----Informatica------------------------------

INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Guacamayo'
WHERE g.nombre_grado IN ('Quinto de primaria','Sexto de primaria');

-- Bufeo (Primero a Tercero de secundaria)
INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Bufeo'
WHERE g.nombre_grado IN ('Primero de secundaria','Segundo de secundaria','Tercero de secundaria');

-- Puma (Cuarto a Sexto de secundaria)
INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Puma'
WHERE g.nombre_grado IN ('Cuarto de secundaria','Quinto de secundaria','Sexto de secundaria');

--Matematicas---------------------------------------------------

INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Primer nivel'
WHERE g.nombre_grado IN ('Primero de secundaria','Segundo de secundaria');

-- Segundo nivel (Tercero y Cuarto de secundaria)
INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Segundo nivel'
WHERE g.nombre_grado IN ('Tercero de secundaria','Cuarto de secundaria');

-- Tercer nivel (Quinto de secundaria)
INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Tercer nivel'
WHERE g.nombre_grado = 'Quinto de secundaria';

-- Cuarto nivel (Sexto de secundaria)
INSERT INTO nivel_grado
    (id_grado, id_nivel, id_olimpiada, created_at, updated_at)
SELECT g.id, n.id, 1, NOW(), NOW()
FROM grado g
    JOIN nivel n ON n.nombre_nivel = 'Cuarto nivel'
WHERE g.nombre_grado = 'Sexto de secundaria';

---Vinculación por area--------------------
INSERT INTO area_nivel
    (id_area, id_nivel, id_olimpiada, created_at, updated_at)
SELECT a.id, n.id, 1, NOW(), NOW()
FROM area a
    JOIN nivel n ON n.nombre_nivel IN (
    'Tercero de primaria','Cuarto de primaria','Quinto de primaria','Sexto de primaria',
    'Primero de secundaria','Segundo de secundaria','Tercero de secundaria',
    'Cuarto de secundaria','Quinto de secundaria','Sexto de secundaria'
)
WHERE a.nombre_area = 'Astrofisica';

INSERT INTO area_nivel
    (id_area, id_nivel, id_olimpiada, created_at, updated_at)
SELECT a.id, n.id, 1, NOW(), NOW()
FROM area a
    JOIN nivel n ON n.nombre_nivel IN (
    'Segundo de secundaria','Tercero de secundaria','Cuarto de secundaria',
    'Quinto de secundaria','Sexto de secundaria'
)
WHERE a.nombre_area = 'Quimica';

INSERT INTO area_nivel
    (id_area, id_nivel, id_olimpiada, created_at, updated_at)
SELECT a.id, n.id, 1, NOW(), NOW()
FROM area a
    JOIN nivel n
    ON n.nombre_nivel IN ('Segundo de secundaria','Tercero de secundaria','Cuarto de secundaria','Quinto de secundaria','Sexto de secundaria')
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

CREATE OR REPLACE FUNCTION fn_log_competidor
() RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
    INSERT INTO competidor_auditoria
        (competidor_id, accion, datos)
    VALUES(NEW.id, TG_OP, row_to_json(NEW));
    RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
    INSERT INTO competidor_auditoria
        (competidor_id, accion, datos)
    VALUES(NEW.id, TG_OP, row_to_json(NEW));
    RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
    INSERT INTO competidor_auditoria
        (competidor_id, accion, datos)
    VALUES(OLD.id, TG_OP, row_to_json(OLD));
    RETURN OLD;
END
IF;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_competidor_auditoria
AFTER
INSERT OR
UPDATE OR DELETE ON competidor
FOR EACH ROW
EXECUTE FUNCTION fn_log_competidor
();
