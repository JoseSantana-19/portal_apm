<#
    ============================================================================
    Backups PORTABLES para SQL Server 2014 -- las 5 bases de Portal APM
    ============================================================================
    IMPORTANTE -- por qué esto NO es un .bak:
    Un backup nativo (BACKUP DATABASE) tomado en un motor SQL Server 2022 queda
    en formato 2022 y NO se puede restaurar en un SQL Server 2014 -- los backups
    de SQL Server solo son compatibles hacia ADELANTE (un motor más nuevo puede
    leer un backup viejo, nunca al revés). No existe ninguna opción de
    BACKUP DATABASE que lo evite.

    La única forma real de llevar estas bases a un SQL Server 2014 es un script
    de texto plano (CREATE + INSERT) que SQL Server 2014 pueda ejecutar tal
    cual -- exactamente lo que genera este script, usando SMO
    (TargetServerVersion = 120) igual que ya hace el proyecto en
    scripts/generar_inventario1_sql2014.ps1 de Control de Bienes, pero
    generalizado a las 5 bases reales del sistema en vez de una sola.

    Salida: un .sql por base en la carpeta destino, nombre
    "<NombreBD>_sql2014_<fecha_hora>.sql". Cada archivo es autocontenido: crea
    la base, fija COMPATIBILITY_LEVEL=120, crea todo el esquema (tablas,
    índices, PK/FK/UNIQUE/CHECK, disparadores) y carga todos los datos con
    SET IDENTITY_INSERT automático por tabla (lo arma el propio SMO).

    Requisitos:
      - Windows PowerShell 5.1 (powershell.exe) -- el módulo SQLPS/SMO NO carga
        en PowerShell 7 (pwsh.exe). Verificado disponible en este equipo.
      - Autenticación de Windows contra la instancia (igual que
        config/connections.php del proyecto) -- si el servidor usa SQL Auth,
        pasar -Usuario/-Clave.

    Uso:
        powershell.exe -ExecutionPolicy Bypass -File generar_backups_sql2014.ps1
        powershell.exe -ExecutionPolicy Bypass -File generar_backups_sql2014.ps1 -Server ".\VICTUS" -DestinoCarpeta "D:\otra\ruta"

    A diferencia de backup_completo_5_bases.sql (el .bak nativo, para
    restaurar rápido en OTRO SQL Server 2022/2019/2017/2016), este script
    escribe los archivos como TU PROPIO usuario de Windows (no como el
    servicio de SQL Server) -- no hace falta el permiso NTFS que sí pide el
    otro script para escribir en OneDrive.
    ============================================================================
#>
[CmdletBinding()]
param(
    [string]$Server          = '.\VICTUS',
    [string]$DestinoCarpeta  = 'C:\Users\Usuario\OneDrive\Documentos\BD PORTUARIA',
    [string]$Usuario         = '',
    [string]$Clave           = '',
    # Por defecto genera las 5. Pasar -Bases 'Talento_Humano' para regenerar
    # solo una (util despues de un parche como Repair-TalentoHumano2014).
    [string[]]$Bases         = @('PORTAL_APM', 'Talento_Humano', 'inventario', 'PortuariaDemo', 'PortuariaExterna')
)

$ErrorActionPreference = 'Stop'

if ($PSVersionTable.PSEdition -ne 'Desktop') {
    throw 'Ejecute este script con Windows PowerShell (powershell.exe), no con PowerShell 7 (pwsh.exe) -- el modulo SQLPS/SMO no carga en pwsh.'
}
Import-Module SQLPS -DisableNameChecking -ErrorAction Stop

if (-not (Test-Path -LiteralPath $DestinoCarpeta)) {
    New-Item -ItemType Directory -Path $DestinoCarpeta -Force | Out-Null
}

$sello = Get-Date -Format 'yyyyMMdd_HHmmss'

<#
    Repair-TalentoHumano2014 -- parches PUNTUALES de compatibilidad real,
    aplicados SOLO sobre el archivo .sql exportado (nunca sobre la base viva
    en 2022, que sigue usando AT TIME ZONE/ISJSON/STRING_AGG/STRING_SPLIT sin
    problema -- son elecciones correctas ahi, el problema es unicamente que
    SQL Server 2014 no tiene esas 4 funciones/clausulas.

    SMO (TargetServerVersion=120) reescribe la sintaxis DDL de tablas/indices,
    pero COPIA TAL CUAL el cuerpo de procedimientos/funciones/vistas -- no
    traduce lenguaje T-SQL. Escaneo real contra el servidor (2026-08-30)
    encontro exactamente 6 objetos + 1 constraint de Talento_Humano con
    sintaxis posterior a 2014 (ningun TRIM() real en ninguna de las 5 bases --
    el escaneo inicial que reporto ~20 tenia un bug, matcheaba LTRIM/RTRIM
    como falsos positivos):

      - fn_th_fecha_institucional      : AT TIME ZONE       (2016+)
      - CK_th_paz_seccion_json         : ISJSON             (2016+)
      - sp_th_guardar_seccion_paz_salvo: ISJSON             (2016+)
      - sp_th_mover_empleados_lote     : ISJSON + OPENJSON  (2016+)
      - sp_th_sincronizar_nacionalidades_empleado: ISJSON + OPENJSON (2016+)
      - sp_th_obtener_expediente_impresion: STRING_AGG      (2017+)
      - sp_th_buscar_personal          : STRING_SPLIT       (2016+)

    Cada reemplazo preserva el comportamiento real (no una aproximacion),
    excepto donde 2014 no tiene NINGUNA forma de lograrlo (validacion real
    de gramatica JSON vía ISJSON) -- ahi se documenta explicito el limite.
#>
function Repair-TalentoHumano2014 {
    param([string]$RutaArchivo)

    Write-Host "  Aplicando parches de compatibilidad real SQL Server 2014..." -ForegroundColor Yellow
    $texto = Get-Content -LiteralPath $RutaArchivo -Raw -Encoding UTF8

    function Reemplazar-Objeto([string]$texto, [string]$tipo, [string]$nombre, [string]$nuevoCuerpo) {
        # Desde "CREATE <tipo> [dbo].<nombre>" (con o sin corchetes) hasta el
        # proximo "GO" en su propia linea -- asi es como este mismo script
        # separa cada batch al escribir el archivo.
        # Lookahead (no consume) por espacio o '(' -- cubre tanto funciones
        # ("nombre()" sin espacio) como procedimientos sin parentesis en la
        # lista de parametros ("nombre @param ..."), sin matchear nombres
        # que sean prefijo de otro objeto real.
        $patron = "(?s)CREATE\s+$tipo\s+(?:\[?dbo\]?\.)?\[?$nombre\]?(?=[\s(]).*?(?=\r?\nGO)"
        $rx = [regex]::new($patron, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
        if (-not $rx.IsMatch($texto)) {
            Write-Warning "    No se encontro $tipo $nombre en el archivo -- se deja sin tocar (revisar a mano)."
            return $texto
        }
        return $rx.Replace($texto, [System.Text.RegularExpressions.MatchEvaluator]{ param($m) $nuevoCuerpo }, 1)
    }

    # 1) fn_th_fecha_institucional -- AT TIME ZONE (2016+). 'SA Pacific
    #    Standard Time' es UTC-5 fijo todo el ano (Ecuador/Peru/Colombia no
    #    observan horario de verano) -- DATEADD fijo es exactamente
    #    equivalente, no una aproximacion.
    $texto = Reemplazar-Objeto $texto 'FUNCTION' 'fn_th_fecha_institucional' @'
CREATE FUNCTION dbo.fn_th_fecha_institucional()
RETURNS DATE
AS
BEGIN
    /* SQL Server 2014 no tiene AT TIME ZONE (2016+). 'SA Pacific Standard
       Time' es UTC-5 fijo todo el ano (Ecuador/Peru/Colombia no observan
       horario de verano) -- DATEADD fijo es exactamente equivalente. */
    RETURN CONVERT(date, DATEADD(HOUR, -5, SYSUTCDATETIME()));
END
'@

    # 2) CK_th_paz_seccion_json -- ISJSON (2016+) en un CHECK constraint.
    #    2014 no tiene NINGUNA forma de validar gramatica JSON real -- se
    #    reemplaza por un chequeo estructural (empieza/termina con los
    #    delimitadores correctos), documentado como limite real, no oculto.
    $patronCk = '(?i)isjson\s*\(\s*\[?datos_json\]?\s*\)\s*=\s*\(?1\)?'
    $nuevoCk  = "(left(ltrim([datos_json]),(1))='{' OR left(ltrim([datos_json]),(1))='[') AND (right(rtrim([datos_json]),(1))='}' OR right(rtrim([datos_json]),(1))=']')"
    if ($texto -match $patronCk) {
        $texto = [regex]::Replace($texto, $patronCk, [System.Text.RegularExpressions.MatchEvaluator]{ param($m) $nuevoCk })
    } else {
        Write-Warning "    No se encontro la expresion ISJSON de CK_th_paz_seccion_json -- revisar a mano."
    }

    # 3) sp_th_guardar_seccion_paz_salvo -- ISJSON (2016+), mismo chequeo
    #    estructural que el constraint de arriba.
    $texto = Reemplazar-Objeto $texto 'PROCEDURE' 'sp_th_guardar_seccion_paz_salvo' @'
CREATE PROCEDURE dbo.sp_th_guardar_seccion_paz_salvo
 @paz_salvo_id INT,@codigo_seccion VARCHAR(20),@estado VARCHAR(15),@datos_json NVARCHAR(MAX),@observaciones NVARCHAR(1000),
 @responsable_nombre NVARCHAR(150),@responsable_puesto NVARCHAR(150),@sumilla NVARCHAR(100),@usuario VARCHAR(50),@ip VARCHAR(45)
AS
BEGIN
 SET NOCOUNT ON;SET XACT_ABORT ON;
 BEGIN TRY
  BEGIN TRAN;
  SET @codigo_seccion=UPPER(LTRIM(RTRIM(@codigo_seccion)));SET @estado=UPPER(LTRIM(RTRIM(@estado)));
  /* SQL Server 2014 no tiene ISJSON() (2016+) -- chequeo estructural en vez
     de validacion real de gramatica JSON (2014 no puede hacer eso de
     ninguna forma). */
  DECLARE @json_ok BIT = CASE WHEN (LEFT(LTRIM(@datos_json),1) IN ('{','[')) AND (RIGHT(RTRIM(@datos_json),1) IN ('}',']')) THEN 1 ELSE 0 END;
  IF @json_ok<>1 THROW 52120,'Los datos de la seccion no son JSON valido.',1;
  IF @estado NOT IN('PENDIENTE','CONFORME','OBSERVADO') THROW 52121,'Estado de seccion no valido.',1;
  UPDATE dbo.th_paz_salvo_secciones SET estado=@estado,datos_json=@datos_json,observaciones=NULLIF(@observaciones,''),responsable_nombre=NULLIF(@responsable_nombre,''),
   responsable_puesto=NULLIF(@responsable_puesto,''),sumilla=NULLIF(@sumilla,''),fecha_revision=CASE WHEN @estado='PENDIENTE' THEN NULL ELSE SYSDATETIME() END,
   usuario_actualiza=@usuario,fecha_actualizacion=SYSDATETIME() WHERE paz_salvo_id=@paz_salvo_id AND codigo_seccion=@codigo_seccion;
  IF @@ROWCOUNT=0 THROW 52122,'No existe la seccion solicitada.',1;
  DECLARE @total INT,@conformes INT,@observados INT;SELECT @total=COUNT(*),@conformes=SUM(IIF(estado='CONFORME',1,0)),@observados=SUM(IIF(estado='OBSERVADO',1,0)) FROM dbo.th_paz_salvo_secciones WHERE paz_salvo_id=@paz_salvo_id;
  UPDATE dbo.th_paz_salvo SET estado=CASE WHEN @conformes=@total THEN 'COMPLETO' WHEN @observados>0 THEN 'OBSERVADO' WHEN @conformes>0 THEN 'PARCIAL' ELSE 'EN_REVISION' END,
    usuario_actualiza=@usuario,fecha_actualizacion=SYSDATETIME() WHERE paz_salvo_id=@paz_salvo_id AND estado<>'CERRADO';
  DECLARE @detalle_auditoria NVARCHAR(500)=CONCAT(N'Actualizo ',@codigo_seccion,N' del Paz y Salvo #',@paz_salvo_id,N' como ',@estado,N'.');
  EXEC dbo.sp_th_registrar_auditoria @usuario,'Paz y Salvo','ACTUALIZAR_SECCION',@detalle_auditoria,@ip;
  COMMIT;SELECT 1 exito,'Seccion guardada y estado general actualizado.' mensaje;
 END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 exito,ERROR_MESSAGE() mensaje;END CATCH
END
'@

    # 4) sp_th_mover_empleados_lote -- ISJSON+OPENJSON (2016+). El array es
    #    siempre una lista plana de enteros (ej. [12,45,78]) -- se separa a
    #    mano con XML (tecnica estandar pre-2016), sin depender de ningun
    #    parser JSON real. El orden no importa aca (el proc original usaba
    #    DISTINCT, no ordenaba por posicion).
    $texto = Reemplazar-Objeto $texto 'PROCEDURE' 'sp_th_mover_empleados_lote' @'
CREATE PROCEDURE dbo.sp_th_mover_empleados_lote
    @empleados_json NVARCHAR(MAX),@unidad_destino_id INT,@fecha_movimiento DATE,@motivo VARCHAR(500),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        /* SQL Server 2014 no tiene ISJSON()/OPENJSON() (2016+). El array es
           siempre una lista plana de enteros -- se separa a mano quitando
           los corchetes y partiendo por coma via XML (tecnica estandar
           pre-2016). */
        IF (LEFT(LTRIM(@empleados_json),1)<>'[' OR RIGHT(RTRIM(@empleados_json),1)<>']') THROW 51820,'Seleccion no valida.',1;
        DECLARE @s TABLE(empleado_id INT PRIMARY KEY,unidad_id INT,puesto_id INT);
        DECLARE @xml XML = CAST('<i>' + REPLACE(REPLACE(REPLACE(LTRIM(RTRIM(@empleados_json)),'[',''),']',''),',','</i><i>') + '</i>' AS XML);
        INSERT @s(empleado_id)
        SELECT DISTINCT v FROM (
            SELECT TRY_CONVERT(INT, LTRIM(RTRIM(T.c.value('.','VARCHAR(20)')))) AS v
            FROM @xml.nodes('/i') AS T(c)
        ) x WHERE v IS NOT NULL;
        IF (SELECT COUNT(*) FROM @s)<2 THROW 51821,'Seleccione al menos dos empleados.',1;
        UPDATE s SET unidad_id=e.unidad_id,puesto_id=e.puesto_id FROM @s s JOIN dbo.th_empleados e WITH(UPDLOCK,HOLDLOCK) ON e.empleado_id=s.empleado_id AND e.estado=1;
        IF EXISTS(SELECT 1 FROM @s WHERE unidad_id IS NULL OR puesto_id IS NULL) THROW 51822,'La seleccion contiene empleados no disponibles.',1;
        IF EXISTS(SELECT 1 FROM @s WHERE unidad_id=@unidad_destino_id) THROW 51823,'Al menos un empleado ya pertenece al area de destino.',1;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_unidades_organizacionales WHERE unidad_id=@unidad_destino_id AND activo=1) THROW 51824,'Area de destino no valida.',1;
        IF NULLIF(LTRIM(RTRIM(@motivo)),'') IS NULL THROW 51825,'El motivo es obligatorio.',1;
        INSERT dbo.th_movimientos_lote(unidad_destino_id,puesto_destino_id,fecha_movimiento,motivo,cantidad,usuario_crea,direccion_ip)
        VALUES(@unidad_destino_id,NULL,@fecha_movimiento,LTRIM(RTRIM(@motivo)),(SELECT COUNT(*) FROM @s),@usuario,@ip);
        DECLARE @lote_id INT=CONVERT(INT,SCOPE_IDENTITY());
        INSERT dbo.th_movimientos_personal(empleado_id,unidad_origen_id,puesto_origen_id,unidad_destino_id,puesto_destino_id,fecha_movimiento,motivo,usuario_crea,direccion_ip,lote_id)
        SELECT empleado_id,unidad_id,puesto_id,@unidad_destino_id,puesto_id,@fecha_movimiento,LTRIM(RTRIM(@motivo)),@usuario,@ip,@lote_id FROM @s;
        UPDATE h SET fecha_hasta=CASE WHEN h.fecha_desde<@fecha_movimiento THEN DATEADD(DAY,-1,@fecha_movimiento) ELSE @fecha_movimiento END
        FROM dbo.th_historial_laboral h JOIN @s s ON s.empleado_id=h.empleado_id WHERE h.fecha_hasta IS NULL;
        INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,observaciones,usuario_crea,fecha_creacion,movimiento_id,
            tipo_contrato,sueldo_rmu,proceso_institucional,nivel_gestion,lugar_trabajo,grupo_ocupacional,grado_laboral,partida_individual,jornada,horas_jornada,condicion_especial)
        SELECT e.empleado_id,e.puesto_id,@unidad_destino_id,@fecha_movimiento,CONCAT('Movimiento grupal de area #',@lote_id,'. ',LTRIM(RTRIM(@motivo))),@usuario,SYSDATETIME(),m.movimiento_id,
            e.tipo_contrato,e.sueldo_rmu,e.proceso_institucional,e.nivel_gestion,e.lugar_trabajo,e.grupo_ocupacional,e.grado_laboral,e.partida_individual,e.jornada,e.horas_jornada,e.condicion_especial
        FROM dbo.th_empleados e JOIN @s s ON s.empleado_id=e.empleado_id JOIN dbo.th_movimientos_personal m ON m.lote_id=@lote_id AND m.empleado_id=e.empleado_id;
        UPDATE e SET unidad_id=@unidad_destino_id FROM dbo.th_empleados e JOIN @s s ON s.empleado_id=e.empleado_id;
        DECLARE @cantidad INT=(SELECT COUNT(*) FROM @s);
        DECLARE @auditoria_lote NVARCHAR(500)=CONCAT('Lote #',@lote_id,'; ',@cantidad,' empleados; cargos conservados.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Movimiento de Personal','MOVER_LOTE_AREA',@auditoria_lote,@ip;
        COMMIT;SELECT 1 exito,@lote_id lote_id,@cantidad cantidad,'Movimiento grupal aplicado; cargos conservados.' mensaje;
    END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 exito,0 lote_id,0 cantidad,ERROR_MESSAGE() mensaje;END CATCH
END
'@

    # 5) sp_th_sincronizar_nacionalidades_empleado -- ISJSON+OPENJSON
    #    (2016+). Aca el ORDEN si importa (orden=1 marca es_principal) --
    #    se usa la posicion real dentro del XML (XQuery "<<", conteo de
    #    nodos precedentes), no el orden fisico de filas devueltas (que
    #    nodes() no garantiza formalmente aunque en la practica lo respete).
    $texto = Reemplazar-Objeto $texto 'PROCEDURE' 'sp_th_sincronizar_nacionalidades_empleado' @'
CREATE PROCEDURE dbo.sp_th_sincronizar_nacionalidades_empleado
    @empleado_id INT,@nacionalidades_json NVARCHAR(MAX),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id)
            THROW 51300,'El empleado indicado no existe.',1;
        /* SQL Server 2014 no tiene ISJSON()/OPENJSON() (2016+) -- mismo
           reemplazo por XML que sp_th_mover_empleados_lote, pero aca el
           orden si importa (orden=1 marca es_principal): se calcula con
           XQuery "<<" (conteo de nodos precedentes), no con el orden fisico
           de filas devueltas por nodes(), que SQL Server no garantiza
           formalmente. */
        IF (LEFT(LTRIM(@nacionalidades_json),1)<>'[' OR RIGHT(RTRIM(@nacionalidades_json),1)<>']') THROW 51301,'La lista de nacionalidades no es valida.',1;
        DECLARE @ids TABLE(id INT PRIMARY KEY,orden INT);
        DECLARE @xml XML = CAST('<i>' + REPLACE(REPLACE(REPLACE(LTRIM(RTRIM(@nacionalidades_json)),'[',''),']',''),',','</i><i>') + '</i>' AS XML);
        INSERT @ids(id,orden)
        SELECT v, pos FROM (
            SELECT TRY_CONVERT(INT, LTRIM(RTRIM(T.c.value('.','VARCHAR(20)')))) AS v,
                   T.c.value('for $n in . return count(../*[. << $n]) + 1', 'int') AS pos
            FROM @xml.nodes('/i') AS T(c)
        ) x WHERE v IS NOT NULL;
        IF EXISTS(SELECT 1 FROM @ids i LEFT JOIN dbo.th_nacionalidades n ON n.nacionalidad_id=i.id AND n.activo=1 WHERE n.nacionalidad_id IS NULL)
            THROW 51302,'Una nacionalidad seleccionada no existe o esta inactiva.',1;
        DELETE FROM dbo.th_empleado_nacionalidades WHERE empleado_id=@empleado_id;
        INSERT dbo.th_empleado_nacionalidades(empleado_id,nacionalidad_id,es_principal,orden,usuario_crea)
        SELECT @empleado_id,id,CASE WHEN orden=1 THEN 1 ELSE 0 END,orden,@usuario FROM @ids;
        UPDATE e SET nacionalidad=(SELECT TOP 1 n.nombre FROM @ids i JOIN dbo.th_nacionalidades n ON n.nacionalidad_id=i.id ORDER BY i.orden)
        FROM dbo.th_empleados e WHERE e.empleado_id=@empleado_id;
        DECLARE @detalle VARCHAR(500)=CONCAT('Actualizo nacionalidades del empleado #',@empleado_id,'. Total=',(SELECT COUNT(*) FROM @ids));
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Directorio de Personal','ACTUALIZAR_NACIONALIDADES',@detalle,@ip;
        COMMIT;
        SELECT 1 exito,'Nacionalidades actualizadas.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,ERROR_MESSAGE() mensaje;
    END CATCH
END
'@

    # 6) sp_th_obtener_expediente_impresion -- STRING_AGG (2017+). Tecnica
    #    estandar FOR XML PATH para concatenar con orden preservado --
    #    resultado identico, incluyendo el orden por en.orden.
    $texto = Reemplazar-Objeto $texto 'PROCEDURE' 'sp_th_obtener_expediente_impresion' @'
CREATE PROCEDURE dbo.sp_th_obtener_expediente_impresion
    @empleado_id INT,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Formulario Principal','IMPRIMIR','Consulta de expediente completo para PDF.',@ip;
    SELECT e.*,u.nombre_unidad direccion_area,u.codigo_uorg,p.nombre_puesto cargo,p.codigo_puesto,
           p.remuneracion_unificada rmu_catalogo,padre.nombre_unidad direccion_padre,
           /* SQL Server 2014 no tiene STRING_AGG (2017+) -- FOR XML PATH
              es la tecnica estandar equivalente, con el mismo orden. */
           (SELECT STUFF((
                SELECT ', ' + n2.nombre
                FROM dbo.th_empleado_nacionalidades en2
                JOIN dbo.th_nacionalidades n2 ON n2.nacionalidad_id = en2.nacionalidad_id
                WHERE en2.empleado_id = e.empleado_id
                ORDER BY en2.orden
                FOR XML PATH(''), TYPE
            ).value('.', 'NVARCHAR(MAX)'), 1, 2, '')) nacionalidades
    FROM dbo.th_empleados e
    LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
    LEFT JOIN dbo.th_unidades_organizacionales padre ON padre.unidad_id=u.unidad_padre_id
    LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
    WHERE e.empleado_id=@empleado_id;
END
'@

    # 7) sp_th_buscar_personal -- STRING_SPLIT (2016+). Se separa el
    #    termino UNA sola vez con un CTE recursivo clasico (tecnica
    #    estandar pre-2016) en vez de por cada fila -- mismo resultado,
    #    mas eficiente incluso que el original.
    $texto = Reemplazar-Objeto $texto 'PROCEDURE' 'sp_th_buscar_personal' @'
CREATE PROCEDURE dbo.sp_th_buscar_personal
    @termino NVARCHAR(200)=NULL,@unidad_id INT=NULL,@contrato NVARCHAR(100)=NULL,@estado INT=NULL,
    @pagina INT=1,@tamano INT=1000,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET @pagina=CASE WHEN @pagina<1 THEN 1 ELSE @pagina END;
    SET @tamano=CASE WHEN @tamano<1 THEN 25 WHEN @tamano>1000 THEN 1000 ELSE @tamano END;
    SET @termino=NULLIF(LTRIM(RTRIM(@termino)),'');
    DECLARE @detalle_busqueda VARCHAR(500)=CONCAT('Busqueda compuesta: ',COALESCE(@termino,'(sin termino)'));
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Directorio de Personal','BUSCAR',@detalle_busqueda,@ip;

    /* SQL Server 2014 no tiene STRING_SPLIT (2016+) -- CTE recursivo
       clasico (tecnica estandar pre-2016), calculado UNA sola vez en vez
       de re-partir el termino por cada fila de empleado. */
    DECLARE @Tokens TABLE(valor NVARCHAR(200));
    IF @termino IS NOT NULL
    BEGIN
        ;WITH Partido AS (
            SELECT LTRIM(RTRIM(SUBSTRING(t,1,CHARINDEX(' ',t)-1))) AS valor,
                   SUBSTRING(t,CHARINDEX(' ',t)+1,LEN(t)) AS resto
            FROM (SELECT @termino + ' ' AS t) AS x
            WHERE CHARINDEX(' ', t) > 0
            UNION ALL
            SELECT LTRIM(RTRIM(SUBSTRING(resto,1,CHARINDEX(' ',resto)-1))),
                   SUBSTRING(resto,CHARINDEX(' ',resto)+1,LEN(resto))
            FROM Partido
            WHERE CHARINDEX(' ', resto) > 0
        )
        INSERT @Tokens(valor)
        SELECT valor FROM Partido WHERE valor <> '' OPTION (MAXRECURSION 100);
    END;

    ;WITH Base AS(
        SELECT e.empleado_id,e.identificacion,e.apellidos,e.nombres,e.unidad_id,e.puesto_id,e.estado,e.tipo_contrato,
               u.nombre_unidad,p.nombre_puesto,
               CONCAT(e.identificacion,' ',e.apellidos,' ',e.nombres,' ',u.nombre_unidad,' ',p.nombre_puesto,' ',e.tipo_contrato) texto
        FROM dbo.th_empleados e
        LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
        LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
        WHERE (@unidad_id IS NULL OR e.unidad_id=@unidad_id)
          AND (@estado IS NULL OR e.estado=@estado)
          AND (@contrato IS NULL OR e.tipo_contrato COLLATE Modern_Spanish_CI_AI LIKE '%'+@contrato+'%')
          AND (@termino IS NULL OR NOT EXISTS(
              SELECT 1 FROM @Tokens token
              WHERE CONCAT(e.identificacion,' ',e.apellidos,' ',e.nombres,' ',u.nombre_unidad,' ',p.nombre_puesto,' ',e.tipo_contrato)
                    COLLATE Modern_Spanish_CI_AI NOT LIKE '%'+token.valor+'%'))
    ),Paginada AS(
        SELECT *,COUNT(*) OVER() total_resultados,ROW_NUMBER() OVER(ORDER BY apellidos,nombres,empleado_id) fila FROM Base
    )
    SELECT empleado_id,total_resultados FROM Paginada
    WHERE fila BETWEEN ((@pagina-1)*@tamano)+1 AND @pagina*@tamano ORDER BY fila;
END
'@

    Set-Content -LiteralPath $RutaArchivo -Value $texto -Encoding UTF8 -NoNewline
    Write-Host "  Parches aplicados: fn_th_fecha_institucional, CK_th_paz_seccion_json, sp_th_guardar_seccion_paz_salvo, sp_th_mover_empleados_lote, sp_th_sincronizar_nacionalidades_empleado, sp_th_obtener_expediente_impresion, sp_th_buscar_personal" -ForegroundColor Yellow
}

$serverConnection = New-Object Microsoft.SqlServer.Management.Common.ServerConnection($Server)
if ($Usuario -ne '') {
    $serverConnection.LoginSecure = $false
    $serverConnection.Login = $Usuario
    $serverConnection.SecurePassword = (ConvertTo-SecureString $Clave -AsPlainText -Force)
} else {
    $serverConnection.LoginSecure = $true   # Autenticacion de Windows, igual que config/connections.php
}
$smoServer = New-Object Microsoft.SqlServer.Management.Smo.Server($serverConnection)

function Set-CommonScriptingOptions($options) {
    $options.TargetServerVersion            = [Microsoft.SqlServer.Management.Smo.SqlServerVersion]::Version120
    $options.TargetDatabaseEngineType       = [Microsoft.SqlServer.Management.Common.DatabaseEngineType]::Standalone
    $options.SchemaQualify                  = $true
    $options.SchemaQualifyForeignKeysReferences = $true
    $options.Permissions                    = $false
    $options.ScriptOwner                    = $false
    $options.IncludeDatabaseContext         = $false
    $options.IncludeIfNotExists             = $false
    $options.ScriptBatchTerminator          = $false
    $options.AnsiPadding                    = $true
    $options.NoFileGroup                    = $true
    $options.NoFileStream                   = $true
    $options.NoFileStreamColumn             = $true
    $options.NoIndexPartitioningSchemes     = $true
    $options.ScriptDataCompression          = $false
    $options.ContinueScriptingOnError       = $false
}

$resumen = @()

foreach ($nombreBD in $Bases) {
    Write-Host ""
    $db = $smoServer.Databases[$nombreBD]
    if ($null -eq $db) {
        Write-Warning "OMITIDA: $nombreBD no existe en $Server."
        $resumen += "OMITIDA  $nombreBD (no existe)"
        continue
    }

    $destino = Join-Path $DestinoCarpeta "${nombreBD}_sql2014_${sello}.sql"
    Write-Host "=== $nombreBD -> $destino ===" -ForegroundColor Cyan

    try {
        $tables = @($db.Tables | Where-Object { -not $_.IsSystemObject } | Sort-Object Schema, Name)
        if ($tables.Count -eq 0) {
            Write-Warning "  $nombreBD no tiene tablas de usuario -- se genera igual el script de creacion de la base."
        }

        $utf8Bom = New-Object System.Text.UTF8Encoding($true)
        $writer  = New-Object System.IO.StreamWriter($destino, $false, $utf8Bom)

        try {
            $writer.WriteLine("/*")
            $writer.WriteLine("  $nombreBD -- script portable para SQL Server 2014 (compatibilidad 120).")
            $writer.WriteLine("  Contiene esquema completo (tablas, indices, PK/FK/UNIQUE/CHECK, disparadores)")
            $writer.WriteLine("  y todos los datos. Generado: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz')")
            $writer.WriteLine("*/")
            $writer.WriteLine('SET NOCOUNT ON;')
            $writer.WriteLine('SET XACT_ABORT ON;')
            $writer.WriteLine('GO')
            $writer.WriteLine("IF DB_ID(N'$nombreBD') IS NOT NULL")
            $writer.WriteLine('BEGIN')
            $writer.WriteLine("    RAISERROR(N'La base $nombreBD ya existe en este servidor -- el script se detuvo para no sobrescribirla.', 16, 1);")
            $writer.WriteLine('    SET NOEXEC ON;')
            $writer.WriteLine('END')
            $writer.WriteLine('GO')
            $writer.WriteLine("CREATE DATABASE [$nombreBD];")
            $writer.WriteLine('GO')
            $writer.WriteLine("ALTER DATABASE [$nombreBD] SET COMPATIBILITY_LEVEL = 120;")
            $writer.WriteLine('GO')
            $writer.WriteLine("USE [$nombreBD];")
            $writer.WriteLine('GO')

            if ($tables.Count -gt 0) {
                $schemaScripter = New-Object Microsoft.SqlServer.Management.Smo.Scripter($smoServer)
                Set-CommonScriptingOptions $schemaScripter.Options
                $schemaScripter.Options.ScriptSchema        = $true
                $schemaScripter.Options.ScriptData          = $false
                $schemaScripter.Options.WithDependencies    = $false
                $schemaScripter.Options.DriAll              = $true
                $schemaScripter.Options.DriIncludeSystemNames = $true
                $schemaScripter.Options.Indexes             = $true
                $schemaScripter.Options.ClusteredIndexes    = $true
                $schemaScripter.Options.NonClusteredIndexes = $true
                $schemaScripter.Options.Triggers            = $true

                # No solo tablas -- vistas, funciones y procedimientos
                # standalone (sin ninguna tabla que los "necesite" via FK/
                # constraint/trigger) quedaban afuera del export si solo se
                # pasaban las tablas. El orden de dependencias se resuelve
                # a mano abajo (DiscoverDependencies/WalkDependencies) en
                # vez de con Options.WithDependencies=true, porque ese modo
                # sigue referencias cruzadas a OTRAS bases del mismo
                # servidor (ej.: inventario.dbo.vw_inv_talento_personal
                # referencia Talento_Humano.dbo.vw_th_directorio_empleados
                # con nombre de 3 partes) y copia ese objeto ajeno -- con
                # su cuerpo tal cual, que asume que vive en su base de
                # origen -- DENTRO de este script, rompiendo la
                # restauracion. Bug real detectado en SQL Server 2014:
                # "Mens 208 ... El nombre de objeto 'dbo.th_empleados' no
                # es valido" al restaurar el export de inventario, porque
                # arrastro esa vista de Talento_Humano completa.
                $vistas = @($db.Views | Where-Object { -not $_.IsSystemObject })
                $funciones = @($db.UserDefinedFunctions | Where-Object { -not $_.IsSystemObject })
                $procedimientos = @($db.StoredProcedures | Where-Object { -not $_.IsSystemObject })

                # Options.Triggers=true (arriba) promete incluir los
                # disparadores como parte del script de su tabla dueña,
                # pero probado contra este servidor real NO los genera --
                # EnumScript() sobre el Urn de la tabla nunca emite
                # "CREATE TRIGGER" pese a Options.Triggers=true (verificado
                # con y sin WithDependencies). Scriptear el Urn del
                # disparador DIRECTAMENTE si funciona, asi que se listan
                # aparte como objetos propios, igual que vistas/funciones/
                # procedimientos, en vez de confiar en el bundling roto.
                $disparadores = @()
                foreach ($t in $tables) { $disparadores += @($t.Triggers | Where-Object { -not $_.IsSystemObject }) }

                Write-Host "  Escribiendo esquema ($($tables.Count) tablas, $($vistas.Count) vistas, $($funciones.Count) funciones, $($procedimientos.Count) procedimientos, $($disparadores.Count) disparadores)..."
                $todosLosObjetos = @($tables) + @($vistas) + @($funciones) + @($procedimientos) + @($disparadores)
                $urnsPropios = [Microsoft.SqlServer.Management.Sdk.Sfc.Urn[]]@($todosLosObjetos | ForEach-Object { $_.Urn })

                $tableUrns = $urnsPropios
                try {
                    $caminanteDependencias = New-Object Microsoft.SqlServer.Management.Smo.Scripter($smoServer)
                    $arbolDependencias = $caminanteDependencias.DiscoverDependencies($urnsPropios, $false)
                    $coleccionOrdenada = $caminanteDependencias.WalkDependencies($arbolDependencias)
                    $marcaBaseDatos = "Database[@Name='$nombreBD']"
                    $tableUrns = [Microsoft.SqlServer.Management.Sdk.Sfc.Urn[]]@(
                        $coleccionOrdenada | Where-Object { $_.Urn.Value.Contains($marcaBaseDatos) } | ForEach-Object { $_.Urn }
                    )
                    $descartados = @($coleccionOrdenada | Where-Object { -not $_.Urn.Value.Contains($marcaBaseDatos) })
                    if ($descartados.Count -gt 0) {
                        Write-Warning ("  Se descartaron {0} objeto(s) de OTRA base de datos que la resolucion de dependencias habia arrastrado: {1}" -f $descartados.Count, (($descartados | ForEach-Object { $_.Urn.Value }) -join '; '))
                    }
                } catch {
                    Write-Warning "  Aviso: resolución automática de dependencias omitida ($($_.Exception.Message)) -- ordenando por tipo de objeto (Tablas -> Vistas -> Funciones -> Procs -> Disparadores)."
                    $ordenadosPorTipo = @($tables) + @($funciones) + @($vistas) + @($procedimientos) + @($disparadores)
                    $tableUrns = [Microsoft.SqlServer.Management.Sdk.Sfc.Urn[]]@($ordenadosPorTipo | ForEach-Object { $_.Urn })
                }

                foreach ($batch in $schemaScripter.EnumScript($tableUrns)) {
                    if (-not [string]::IsNullOrWhiteSpace($batch)) {
                        $writer.WriteLine($batch.TrimEnd())
                        $writer.WriteLine('GO')
                        # Reafirmar despues de CADA objeto -- algunas vistas/
                        # procedimientos viejos fueron creados originalmente
                        # con alguna de estas opciones SET en OFF, y SMO
                        # reproduce ese SET antes de crearlos sin revertirlo
                        # despues. Esa bandera queda pegada para TODO lo que
                        # sigue en la misma conexion y revienta CREATE INDEX
                        # de indices filtrados o vistas indexadas mas
                        # adelante en el archivo (bugs reales: 6 CREATE
                        # INDEX de inventario con "QUOTED_IDENTIFIER", 1 de
                        # PORTAL_APM con "ANSI_PADDING" -- son las 7
                        # opciones que SQL Server exige en ON, salvo
                        # NUMERIC_ROUNDABORT en OFF, para crear indices
                        # filtrados/indexados o columnas calculadas).
                        $writer.WriteLine('SET ANSI_NULLS ON;')
                        $writer.WriteLine('SET QUOTED_IDENTIFIER ON;')
                        $writer.WriteLine('SET ANSI_PADDING ON;')
                        $writer.WriteLine('SET ANSI_WARNINGS ON;')
                        $writer.WriteLine('SET ARITHABORT ON;')
                        $writer.WriteLine('SET CONCAT_NULL_YIELDS_NULL ON;')
                        $writer.WriteLine('SET NUMERIC_ROUNDABORT OFF;')
                        $writer.WriteLine('GO')
                    }
                }

                $writer.WriteLine('/* Desactivar temporalmente relaciones y disparadores durante la carga de datos. */')
                foreach ($table in $tables) {
                    $q = '[' + $table.Schema.Replace(']', ']]') + '].[' + $table.Name.Replace(']', ']]') + ']'
                    $writer.WriteLine("ALTER TABLE $q NOCHECK CONSTRAINT ALL;")
                    $writer.WriteLine("DISABLE TRIGGER ALL ON $q;")
                }
                $writer.WriteLine('GO')

                $dataScripter = New-Object Microsoft.SqlServer.Management.Smo.Scripter($smoServer)
                Set-CommonScriptingOptions $dataScripter.Options
                $dataScripter.Options.ScriptSchema     = $false
                $dataScripter.Options.ScriptData       = $true
                $dataScripter.Options.WithDependencies = $false

                $i = 0
                foreach ($table in $tables) {
                    $i++
                    Write-Host ("  [{0}/{1}] datos de {2}.{3}" -f $i, $tables.Count, $table.Schema, $table.Name)
                    foreach ($batch in $dataScripter.EnumScript([Microsoft.SqlServer.Management.Sdk.Sfc.Urn[]]@($table.Urn))) {
                        if (-not [string]::IsNullOrWhiteSpace($batch)) {
                            $writer.WriteLine($batch.TrimEnd())
                            $writer.WriteLine('GO')
                        }
                    }
                }

                $writer.WriteLine('/* Reactivar y validar relaciones y disparadores despues de cargar los datos. */')
                foreach ($table in $tables) {
                    $q = '[' + $table.Schema.Replace(']', ']]') + '].[' + $table.Name.Replace(']', ']]') + ']'
                    $writer.WriteLine("ALTER TABLE $q WITH CHECK CHECK CONSTRAINT ALL;")
                    $writer.WriteLine("ENABLE TRIGGER ALL ON $q;")
                }
                $writer.WriteLine('GO')
            }

            $writer.WriteLine("PRINT N'$nombreBD restaurada correctamente en SQL Server 2014.';")
            $writer.WriteLine('GO')
        } finally {
            $writer.Dispose()
        }

        if ($nombreBD -eq 'Talento_Humano') {
            Repair-TalentoHumano2014 -RutaArchivo $destino
        }

        $f = Get-Item -LiteralPath $destino
        Write-Host ("  OK -- {0:N2} MB" -f ($f.Length / 1MB)) -ForegroundColor Green
        $resumen += "OK       $nombreBD -> $($f.Name) ({0:N2} MB)" -f ($f.Length / 1MB)
    }
    catch {
        Write-Host "  ERROR generando $nombreBD -- $($_.Exception.Message)" -ForegroundColor Red
        $resumen += "ERROR    $nombreBD -- $($_.Exception.Message)"
        # No dejar un .sql parcial/roto (solo cabecera, sin esquema ni
        # datos) tirado en la carpeta -- se veria como un archivo real.
        if (Test-Path -LiteralPath $destino) {
            Remove-Item -LiteralPath $destino -Force
            Write-Host "  (archivo parcial eliminado: $destino)" -ForegroundColor Yellow
        }
    }
}

$serverConnection.Disconnect()

Write-Host ""
Write-Host "=== Resumen ===" -ForegroundColor Cyan
$resumen | ForEach-Object { Write-Host "  $_" }
