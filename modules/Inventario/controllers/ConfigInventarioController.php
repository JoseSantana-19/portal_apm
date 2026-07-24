<?php
/**
 * ConfigInventarioController — Períodos/IVA y secuenciales del inventario.
 * Estructura MVC nativa de portal_apm. Datos vía sqlsrv nativo (sin PDO).
 */
class ConfigInventarioController extends Controller
{
    private PeriodoModel $periodo;
    private SecuencialModel $sec;

    public function __construct()
    {
        $this->periodo = new PeriodoModel();
        $this->sec     = new SecuencialModel();
    }

    public function periodos(): void
    {
        $this->requireAuth();
        $this->render('Inventario/periodos', [
            'pageTitle' => 'Períodos e IVA',
            'periodos'  => $this->periodo->obtenerTodos(),
            'activo'    => $this->periodo->obtenerPeriodoActivo(),
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function crearPeriodo(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $nombre = trim($_POST['nombre'] ?? '');
        $fIni   = $_POST['fecha_inicio'] ?? '';
        $fFin   = $_POST['fecha_fin'] ?? '';
        $iva    = (float)($_POST['tasa_iva'] ?? 15);

        if ($nombre === '' || $fIni === '' || $fFin === '') {
            SessionHelper::flash('error', 'Completa nombre y fechas del período.');
        } else {
            try {
                $this->periodo->crear($nombre, $fIni, $fFin, $iva);
                SessionHelper::flash('success', 'Período creado y activado.');
            } catch (Throwable $e) {
                SessionHelper::flash('error', 'No se pudo crear el período: ' . $e->getMessage());
            }
        }
        $this->redirect('/inventario/periodos');
    }

    public function secuenciales(): void
    {
        $this->requireAuth();
        $this->render('Inventario/secuenciales', [
            'pageTitle'    => 'Secuenciales',
            'secuenciales' => $this->sec->obtenerTodos(),
            'csrf'         => $this->csrfToken(),
        ]);
    }

    public function reiniciarSecuencial(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $this->requireLevel(3);
        $modulo = $_POST['modulo'] ?? '';
        if ($modulo !== '') {
            $this->sec->reiniciar($modulo);
            SessionHelper::flash('success', "Secuencial '{$modulo}' reiniciado a 0.");
        }
        $this->redirect('/inventario/secuenciales');
    }
}
