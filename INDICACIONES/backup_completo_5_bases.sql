/*
    ============================================================================
    BACKUP COMPLETO — Las 5 bases de datos reales de Portal APM y sus módulos
    ============================================================================
    Genera un .bak completo de cada base en:
        C:\Users\Usuario\OneDrive\Documentos\BD PORTUARIA
    con nombre "<NombreBD>_<fecha_hora>.bak" (mismo sello de tiempo para las 5
    de una misma corrida).

    Bases cubiertas (fuente de verdad: config/connections.php del portal):
        - PORTAL_APM       -> portal nativo (hub)
        - Talento_Humano   -> módulo Talento Humano
        - inventario       -> módulo Control de Bienes
        - PortuariaDemo    -> módulo Bitácoras
        - PortuariaExterna -> Bitácoras, registro de empresas externas

    Motor real verificado: Microsoft SQL Server 2022 Express Edition (16.0.1190.2).
    NO se usa WITH COMPRESSION -- Express no la admite (falla en tiempo de
    ejecución si se agrega).

    ── REQUISITO OBLIGATORIO ANTES DE CORRER ESTE SCRIPT ──────────────────────
    El servicio de SQL Server corre como "NT Service\MSSQL$VICTUS", una cuenta
    de servicio SIN acceso a carpetas de usuario como OneDrive por defecto.
    Verificado en este equipo: un BACKUP de prueba a esa carpeta falla con
    "Acceso denegado" (error de sistema operativo 5) aunque la carpeta exista.

    Solución (una sola vez, como Administrador -- PowerShell o cmd.exe):

        icacls "C:\Users\Usuario\OneDrive\Documentos\BD PORTUARIA" /grant "NT SERVICE\MSSQL$VICTUS:(OI)(CI)M"

    Sin este paso, este script va a fallar con un mensaje claro (ver el
    TRY/CATCH de abajo) en vez de un error críptico de SQL Server.

    Ejecutar con: sqlcmd -S ".\VICTUS" -E -i "backup_completo_5_bases.sql"
    o pegando el contenido en SSMS conectado a la instancia .\VICTUS.
    ============================================================================
*/

SET NOCOUNT ON;

DECLARE @Carpeta   NVARCHAR(260) = N'C:\Users\Usuario\OneDrive\Documentos\BD PORTUARIA';
DECLARE @Sello     NVARCHAR(20)  = CONVERT(NVARCHAR(8), GETDATE(), 112)
                                   + '_' + REPLACE(CONVERT(NVARCHAR(8), GETDATE(), 108), ':', '');
DECLARE @Bases     TABLE (NombreBD SYSNAME);
INSERT INTO @Bases (NombreBD) VALUES
    (N'PORTAL_APM'), (N'Talento_Humano'), (N'inventario'),
    (N'PortuariaDemo'), (N'PortuariaExterna');

PRINT N'=== Backup completo Portal APM — sello ' + @Sello + N' ===';
PRINT N'Carpeta destino: ' + @Carpeta;
PRINT N'';

-- Intento best-effort de crear la carpeta si no existe (no reemplaza el
-- permiso NTFS del requisito de arriba -- si la carpeta ya existe pero sin
-- permiso de escritura, este paso "funciona" igual y el problema real
-- aparece recién en el BACKUP de abajo).
EXEC master.dbo.xp_create_subdir @Carpeta;

DECLARE @NombreBD  SYSNAME;
DECLARE @Archivo   NVARCHAR(400);
DECLARE @SqlBackup NVARCHAR(1000);
DECLARE @SqlVerify NVARCHAR(1000);
DECLARE @Ok        INT = 0;
DECLARE @Fallo     INT = 0;

DECLARE cur CURSOR LOCAL FAST_FORWARD FOR SELECT NombreBD FROM @Bases;
OPEN cur;
FETCH NEXT FROM cur INTO @NombreBD;

WHILE @@FETCH_STATUS = 0
BEGIN
    IF DB_ID(@NombreBD) IS NULL
    BEGIN
        PRINT N'OMITIDA: ' + @NombreBD + N' — no existe en esta instancia.';
    END
    ELSE
    BEGIN
        SET @Archivo = @Carpeta + N'\' + @NombreBD + N'_' + @Sello + N'.bak';

        BEGIN TRY
            SET @SqlBackup = N'BACKUP DATABASE ' + QUOTENAME(@NombreBD)
                + N' TO DISK = N''' + REPLACE(@Archivo, '''', '''''') + N''''
                + N' WITH INIT, CHECKSUM, STATS = 10, NAME = N''' + @NombreBD + N' - backup completo''';
            PRINT N'--- Respaldando ' + @NombreBD + N' -> ' + @Archivo;
            EXEC sp_executesql @SqlBackup;

            -- Verificación real del archivo recién escrito, no solo confiar
            -- en que el comando terminó sin error (disciplina del proyecto:
            -- ver INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md §2.7).
            SET @SqlVerify = N'RESTORE VERIFYONLY FROM DISK = N''' + REPLACE(@Archivo, '''', '''''') + N'''';
            EXEC sp_executesql @SqlVerify;
            PRINT N'    OK — verificado con RESTORE VERIFYONLY.';
            SET @Ok += 1;
        END TRY
        BEGIN CATCH
            SET @Fallo += 1;
            PRINT N'    ERROR respaldando ' + @NombreBD + N': ' + ERROR_MESSAGE();
            IF ERROR_NUMBER() = 3201 OR ERROR_MESSAGE() LIKE '%Acceso denegado%' OR ERROR_MESSAGE() LIKE '%Access is denied%'
            BEGIN
                PRINT N'    >>> Esto es el problema de permisos documentado arriba.';
                PRINT N'    >>> Correr como Administrador (una sola vez):';
                PRINT N'    >>>   icacls "' + @Carpeta + N'" /grant "NT SERVICE\MSSQL$VICTUS:(OI)(CI)M"';
            END
        END CATCH
    END

    FETCH NEXT FROM cur INTO @NombreBD;
END

CLOSE cur;
DEALLOCATE cur;

PRINT N'';
PRINT N'=== Resumen: ' + CAST(@Ok AS NVARCHAR(10)) + N' respaldada(s) OK, ' + CAST(@Fallo AS NVARCHAR(10)) + N' con error. ===';
IF @Fallo > 0
    RAISERROR (N'Al menos un respaldo falló — revisar los mensajes de arriba.', 16, 1);
