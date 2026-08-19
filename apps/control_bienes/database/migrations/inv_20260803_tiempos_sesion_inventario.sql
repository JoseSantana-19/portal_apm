SET NOCOUNT ON;

IF EXISTS (SELECT 1 FROM dbo.inv_parametros WHERE clave = 'tiempo_gracia_sesion')
    UPDATE dbo.inv_parametros
       SET descripcion = 'Tolerancia para responder el aviso antes de cerrar sesión (segundos)'
     WHERE clave = 'tiempo_gracia_sesion';
ELSE
    INSERT INTO dbo.inv_parametros (clave, valor, descripcion)
    VALUES ('tiempo_gracia_sesion', '300', 'Tolerancia para responder el aviso antes de cerrar sesión (segundos)');

IF EXISTS (SELECT 1 FROM dbo.inv_parametros WHERE clave = 'tiempo_vigencia_inventario')
    UPDATE dbo.inv_parametros
       SET descripcion = 'Tiempo fuera de Inventario General antes de liberar la consulta (segundos)'
     WHERE clave = 'tiempo_vigencia_inventario';
ELSE
    INSERT INTO dbo.inv_parametros (clave, valor, descripcion)
    VALUES ('tiempo_vigencia_inventario', '600', 'Tiempo fuera de Inventario General antes de liberar la consulta (segundos)');

UPDATE dbo.inv_parametros
   SET descripcion = 'Tiempo sin actividad antes de mostrar el aviso de sesión (segundos)'
 WHERE clave = 'tiempo_inactividad';
