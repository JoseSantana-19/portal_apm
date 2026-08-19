-- Permisos por menú, sección y tipo de operación.
-- La aplicación crea esta tabla automáticamente; este archivo documenta la migración.
CREATE TABLE inv_permisos_detalle (
    id INTEGER PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    route_key VARCHAR(255) NOT NULL,
    scope_key VARCHAR(255) NOT NULL DEFAULT '*',
    can_read SMALLINT NOT NULL DEFAULT 0,
    can_create SMALLINT NOT NULL DEFAULT 0,
    can_edit SMALLINT NOT NULL DEFAULT 0,
    full_control SMALLINT NOT NULL DEFAULT 0,
    UNIQUE (usuario_id, route_key, scope_key),
    FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
);
