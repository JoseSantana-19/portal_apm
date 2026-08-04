<?php
/**
 * Lógica compartida: ingreso de visita desde POST.
 * Usada por VisitaModel::guardarIngresoDesdePost() (bitacoras/visita/guardar).
 */

require_once __DIR__ . '/bit_apm_fecha_iso.php';
require_once __DIR__ . '/bit_validaciones_ecuador.php';

function visitas_guardar_ingreso_desde_post($conn)
{
    $tieneDetalleMotivo = false;
    $stmtColDetalle = sqlsrv_query($conn, "
        SELECT TOP 1 1 AS existe
        FROM sys.columns
        WHERE object_id = OBJECT_ID(N'dbo.bit_visitas')
          AND name = N'detalle_motivo'
    ");
    if ($stmtColDetalle === false) {
        // Ante duda, preferimos intentar guardar detalle para no perder información.
        $tieneDetalleMotivo = true;
    } elseif ($rowColDetalle = sqlsrv_fetch_array($stmtColDetalle, SQLSRV_FETCH_ASSOC)) {
        $tieneDetalleMotivo = true;
    }

    $nombres = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
    if ($nombres === '' && isset($_POST['nombre'])) {
        $nombres = trim($_POST['nombre']);
    }
    $apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
    if ($apellidos === '' && isset($_POST['apellido'])) {
        $apellidos = trim($_POST['apellido']);
    }
    $genero = isset($_POST['genero']) ? strtoupper(trim((string)$_POST['genero'])) : '';
    $generoParam = in_array($genero, ['M', 'F'], true) ? $genero : null;
    $nidentificacion = isset($_POST['nidentificacion']) ? trim($_POST['nidentificacion']) : '';
    if ($nidentificacion === '' && isset($_POST['cedula'])) {
        $nidentificacion = trim($_POST['cedula']);
    }
    $visitanteGuest = !empty($_POST['visitante_guest']);
    if ($visitanteGuest || $nidentificacion === '') {
        $nidentificacion = '9999999999';
    } elseif ($nidentificacion !== '9999999999') {
        $nidSolo = preg_replace('/\D/', '', $nidentificacion);
        if ($nidSolo === '') {
            header('Location: ' . base_url('visitas/registrar?error=1'));
            exit;
        }
        if (strlen($nidSolo) === 10) {
            if (!ec_validar_cedula_ecuador($nidSolo)) {
                header('Location: ' . base_url('visitas/registrar?error=2'));
                exit;
            }
            $nidentificacion = $nidSolo;
        } elseif (strlen($nidSolo) === 13) {
            if (!ec_validar_ruc_ecuador($nidSolo)) {
                header('Location: ' . base_url('visitas/registrar?error=2'));
                exit;
            }
            $nidentificacion = $nidSolo;
        } else {
            header('Location: ' . base_url('visitas/registrar?error=2'));
            exit;
        }
    }
    $idEmpresa = isset($_POST['id_empresa']) ? trim($_POST['id_empresa']) : '';
    $idFuncionario = isset($_POST['id_funcionario']) && trim((string)$_POST['id_funcionario']) !== ''
        ? (int)$_POST['id_funcionario']
        : null;
    $idDestino = isset($_POST['id_destino']) ? (int)$_POST['id_destino'] : 0;
    $idMotivo = isset($_POST['id_motivo']) ? (int)$_POST['id_motivo'] : 0;
    $detalleMotivo = isset($_POST['detalle_motivo']) ? trim((string)$_POST['detalle_motivo']) : '';
    if (function_exists('mb_substr')) {
        $detalleMotivo = mb_substr($detalleMotivo, 0, 500, 'UTF-8');
    } else {
        $detalleMotivo = substr($detalleMotivo, 0, 500);
    }
    $detalleMotivoParam = ($detalleMotivo === '') ? null : $detalleMotivo;
    if (!$tieneDetalleMotivo && $detalleMotivoParam !== null) {
        $sqlAddDetalle = "
            IF COL_LENGTH('dbo.bit_visitas', 'detalle_motivo') IS NULL
            BEGIN
                ALTER TABLE dbo.bit_visitas
                ADD detalle_motivo NVARCHAR(500) NULL
                    CONSTRAINT DF_bit_visitas_detalle_motivo DEFAULT (N'');
            END
        ";
        $stmtAddDetalle = sqlsrv_query($conn, $sqlAddDetalle);
        if ($stmtAddDetalle !== false) {
            $tieneDetalleMotivo = true;
        }
    }
    $idNivelIncidente = isset($_POST['id_nivel_incidente']) ? (int)$_POST['id_nivel_incidente'] : 0;
    $stDefNivel = sqlsrv_query($conn, 'SELECT TOP 1 id_incidentes FROM dbo.bit_niveles_incidente WHERE estado = 1 ORDER BY nivel');
    $idNivelPorDefecto = 1;
    if ($stDefNivel && ($rDef = sqlsrv_fetch_array($stDefNivel, SQLSRV_FETCH_ASSOC))) {
        $idNivelPorDefecto = (int) $rDef['id_incidentes'];
    }
    if ($idNivelIncidente <= 0) {
        $idNivelIncidente = $idNivelPorDefecto;
    }
    $stNv = sqlsrv_query($conn, 'SELECT id_incidentes FROM dbo.bit_niveles_incidente WHERE id_incidentes = ? AND estado = 1', [$idNivelIncidente]);
    if (!$stNv || !sqlsrv_fetch_array($stNv)) {
        $idNivelIncidente = $idNivelPorDefecto;
    }

    $fechaHoraVisita = isset($_POST['fecha_hora_visita']) ? trim($_POST['fecha_hora_visita']) : '';
    if ($fechaHoraVisita !== '' && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $fechaHoraVisita)) {
        $fechaVisita = substr($fechaHoraVisita, 0, 10);
        $horaEntrada = substr($fechaHoraVisita, 11, 5);
    } else {
        $fechaVisita = isset($_POST['fecha_visita']) ? trim($_POST['fecha_visita']) : '';
        $horaEntrada = isset($_POST['hora_entrada']) ? trim($_POST['hora_entrada']) : '';
    }

    if ($fechaVisita !== '') {
        $isoFv = apm_post_fecha_a_iso($fechaVisita);
        if ($isoFv !== null) {
            $fechaVisita = $isoFv;
        } else {
            header('Location: ' . base_url('visitas/registrar?error=1'));
            exit;
        }
    }

    // Funcionario es opcional. Destino y motivo sí siguen siendo obligatorios para control operativo.
    if ($nombres === '' || $apellidos === '' || $idDestino === 0 || $idMotivo === 0) {
        header('Location: ' . base_url('visitas/registrar?error=1'));
        exit;
    }

    if ($idFuncionario !== null && $idFuncionario <= 0) {
        $idFuncionario = null;
    }

    if ($idEmpresa === '' || $idEmpresa === null) {
        $tipoVisitante = 'Personal';
        $idEmpresaParam = null;
    } else {
        $idEmpresaNum = ctype_digit((string)$idEmpresa) ? (int)$idEmpresa : 0;
        if ($idEmpresaNum > 0) {
            $sqlEmpresa = 'SELECT id_empresa FROM dbo.bit_empresas WHERE id_empresa = ? AND estado = 1';
            $stmtEmpresa = sqlsrv_query($conn, $sqlEmpresa, [$idEmpresaNum]);
            $rowEmpresa = $stmtEmpresa ? sqlsrv_fetch_array($stmtEmpresa, SQLSRV_FETCH_ASSOC) : null;
            if ($rowEmpresa) {
                $tipoVisitante = 'Empresa';
                $idEmpresaParam = $idEmpresaNum;
            } else {
                $tipoVisitante = 'Personal';
                $idEmpresaParam = null;
            }
        } else {
            $tipoVisitante = 'Personal';
            $idEmpresaParam = null;
        }
    }

    $tidentif = 'Cédula';

    if ($nidentificacion === '9999999999') {
        $sqlInsertPersona = 'INSERT INTO dbo.bit_personas (nidentificacion, tidentif, nombres, apellidos, genero, estado) OUTPUT INSERTED.id_persona VALUES (?, ?, ?, ?, ?, 1)';
        $stmtInsertPersona = sqlsrv_query($conn, $sqlInsertPersona, [$nidentificacion, $tidentif, $nombres, $apellidos, $generoParam]);
        if ($stmtInsertPersona === false) {
            echo '<h3>Error al crear la persona (Guest)</h3><pre>';
            print_r(sqlsrv_errors());
            echo '</pre><a href="' . htmlspecialchars(base_url('visitas/registrar')) . '">Volver</a>';
            exit;
        }
        $rowNueva = sqlsrv_fetch_array($stmtInsertPersona, SQLSRV_FETCH_ASSOC);
        $idPersona = $rowNueva ? (int)$rowNueva['id_persona'] : 0;
    } else {
        $sqlBuscarPersona = 'SELECT id_persona, nombres, apellidos FROM dbo.bit_personas WHERE nidentificacion = ?';
        $stmtPersona = sqlsrv_query($conn, $sqlBuscarPersona, [$nidentificacion]);
        if ($stmtPersona === false) {
            echo '<h3>Error al buscar la persona</h3><pre>';
            print_r(sqlsrv_errors());
            echo '</pre><a href="' . htmlspecialchars(base_url('visitas/registrar')) . '">Volver</a>';
            exit;
        }
        $rowPersona = sqlsrv_fetch_array($stmtPersona, SQLSRV_FETCH_ASSOC);
        if ($rowPersona) {
            $idPersona = (int)$rowPersona['id_persona'];
        } else {
            $sqlInsertPersona = 'INSERT INTO dbo.bit_personas (nidentificacion, tidentif, nombres, apellidos, genero, estado) OUTPUT INSERTED.id_persona VALUES (?, ?, ?, ?, ?, 1)';
            $stmtInsertPersona = sqlsrv_query($conn, $sqlInsertPersona, [$nidentificacion, $tidentif, $nombres, $apellidos, $generoParam]);
            if ($stmtInsertPersona === false) {
                echo '<h3>Error al crear la persona</h3><pre>';
                print_r(sqlsrv_errors());
                echo '</pre><a href="' . htmlspecialchars(base_url('visitas/registrar')) . '">Volver</a>';
                exit;
            }
            $rowNueva = sqlsrv_fetch_array($stmtInsertPersona, SQLSRV_FETCH_ASSOC);
            $idPersona = $rowNueva ? (int)$rowNueva['id_persona'] : 0;
        }
    }

    // Regla de negocio: una persona no puede tener dos visitas activas simultáneas.
    // Se permite nuevo ingreso solo cuando la visita anterior tiene hora_salida.
    $sqlVisitaActiva = "
        SELECT TOP 1 id_visita
        FROM dbo.bit_visitas
        WHERE id_persona = ?
          AND hora_salida IS NULL
        ORDER BY id_visita DESC
    ";
    $stVisitaActiva = sqlsrv_query($conn, $sqlVisitaActiva, [$idPersona]);
    $rowVisitaActiva = $stVisitaActiva ? sqlsrv_fetch_array($stVisitaActiva, SQLSRV_FETCH_ASSOC) : null;
    if ($stVisitaActiva) {
        sqlsrv_free_stmt($stVisitaActiva);
    }
    if ($rowVisitaActiva) {
        header('Location: ' . base_url('visitas/registrar?error=3'));
        exit;
    }

    $estadoVisita = 1;

    if ($fechaVisita !== '' && $horaEntrada !== '') {
        if ($tieneDetalleMotivo) {
            $sqlInsert = '
            INSERT INTO dbo.bit_visitas
            (id_persona, tipo_visitante, id_empresa, id_funcionario, id_destino, id_motivo, detalle_motivo, id_nivel_incidente, fecha_visita, hora_entrada, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
        ';
            $params = [
                $idPersona,
                $tipoVisitante,
                $idEmpresaParam,
                $idFuncionario,
                $idDestino,
                $idMotivo,
                $detalleMotivoParam,
                $idNivelIncidente,
                $fechaVisita,
                $horaEntrada,
                $estadoVisita,
            ];
        } else {
            $sqlInsert = '
            INSERT INTO dbo.bit_visitas
            (id_persona, tipo_visitante, id_empresa, id_funcionario, id_destino, id_motivo, id_nivel_incidente, fecha_visita, hora_entrada, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
        ';
            $params = [
                $idPersona,
                $tipoVisitante,
                $idEmpresaParam,
                $idFuncionario,
                $idDestino,
                $idMotivo,
                $idNivelIncidente,
                $fechaVisita,
                $horaEntrada,
                $estadoVisita,
            ];
        }
    } else {
        if ($tieneDetalleMotivo) {
            $sqlInsert = '
            INSERT INTO dbo.bit_visitas
            (id_persona, tipo_visitante, id_empresa, id_funcionario, id_destino, id_motivo, detalle_motivo, id_nivel_incidente, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);
        ';
            $params = [
                $idPersona,
                $tipoVisitante,
                $idEmpresaParam,
                $idFuncionario,
                $idDestino,
                $idMotivo,
                $detalleMotivoParam,
                $idNivelIncidente,
                $estadoVisita,
            ];
        } else {
            $sqlInsert = '
            INSERT INTO dbo.bit_visitas
            (id_persona, tipo_visitante, id_empresa, id_funcionario, id_destino, id_motivo, id_nivel_incidente, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?);
        ';
            $params = [
                $idPersona,
                $tipoVisitante,
                $idEmpresaParam,
                $idFuncionario,
                $idDestino,
                $idMotivo,
                $idNivelIncidente,
                $estadoVisita,
            ];
        }
    }

    $stmt = sqlsrv_query($conn, $sqlInsert, $params);

    if ($stmt === false) {
        echo '<h3>Error al guardar la visita</h3><pre>';
        print_r(sqlsrv_errors());
        echo '</pre><a href="' . htmlspecialchars(base_url('visitas/registrar')) . '">Volver</a>';
        exit;
    }

    header('Location: ' . base_url('visitas?msg=ingreso_ok'));
    exit;
}
