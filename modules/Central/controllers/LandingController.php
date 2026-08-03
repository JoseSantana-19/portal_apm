<?php
/**
 * LandingController — administra el contenido dinámico de la página pública
 * (index.php): carrusel de fondos (CORE_Landing_Imagenes), noticias con
 * imagen (CORE_Landing_Noticias) y consejos/novedades en texto
 * (CORE_Landing_Consejos). Reemplaza lo que antes estaba quemado en el HTML.
 *
 * Noticias y Consejos son entidades separadas a propósito: Noticias siempre
 * lleva imagen (alimenta el carrusel visual), Consejos es texto rotativo sin
 * imagen (franja aparte). No comparten tabla ni semántica.
 */
class LandingController extends Controller {

    // Nodo MOIS: Central > Administración > Contenido del Portal (1,2,6,0).
    private const NODO_LANDING = [1, 2, 6, 0];

    private const EXT_PERMITIDAS = ['jpg', 'jpeg', 'png', 'webp'];
    private const TAM_MAX_BYTES  = 5 * 1024 * 1024; // 5 MB
    private const CARPETA_UPLOAD = 'imgs/landing';

    private function db(): Database { return Database::getInstance(); }

    public function index(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 1]);

        $db = $this->db();
        $imagenes = $db->fetchAll($db->query(
            'SELECT id_imagen, ruta_archivo, orden, estado FROM CORE_Landing_Imagenes ORDER BY orden, id_imagen'
        ));
        $noticias = $db->fetchAll($db->query(
            'SELECT id_noticia, texto, imagen, enlace, orden, estado FROM CORE_Landing_Noticias ORDER BY orden, id_noticia'
        ));
        $consejos = $db->fetchAll($db->query(
            'SELECT id_consejo, texto, enlace, orden, estado FROM CORE_Landing_Consejos ORDER BY orden, id_consejo'
        ));

        $this->render('Central/admin/landing', [
            'pageTitle' => 'Contenido del Portal',
            'imagenes'  => $imagenes,
            'noticias'  => $noticias,
            'consejos'  => $consejos,
            'error'     => SessionHelper::getFlash('error'),
            'success'   => SessionHelper::getFlash('success'),
            'csrf'      => $this->csrfToken(),
        ]);
    }

    // ─── Imágenes de fondo ──────────────────────────────────────────────────────

    public function subirImagen(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 2]);
        $this->verifyCsrf();

        try {
            $ruta = $this->guardarImagenSubida($_FILES['imagen'] ?? null);
        } catch (RuntimeException $e) {
            SessionHelper::flash('error', $e->getMessage());
            $this->redirect('/admin/landing');
        }

        $db = $this->db();
        $maxOrden = (int)($db->fetch($db->query('SELECT ISNULL(MAX(orden), 0) AS m FROM CORE_Landing_Imagenes'))['m'] ?? 0);
        $db->query(
            'INSERT INTO CORE_Landing_Imagenes (ruta_archivo, orden, estado, creado_por) VALUES (?,?,1,?)',
            [
                [$ruta, SQLSRV_PARAM_IN],
                [$maxOrden + 1, SQLSRV_PARAM_IN],
                [(int)($_SESSION['user_id'] ?? 0), SQLSRV_PARAM_IN],
            ]
        );

        SessionHelper::flash('success', 'Imagen agregada al carrusel.');
        $this->redirect('/admin/landing');
    }

    public function toggleImagen(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 3]);
        $this->verifyCsrf();

        $this->db()->query(
            'UPDATE CORE_Landing_Imagenes SET estado = 1 - estado WHERE id_imagen = ?',
            [[$id, SQLSRV_PARAM_IN]]
        );
        $this->redirect('/admin/landing');
    }

    public function moverImagen(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 3]);
        $this->verifyCsrf();

        $this->moverOrden('CORE_Landing_Imagenes', 'id_imagen', $id, (string)($_POST['direccion'] ?? ''));
        $this->redirect('/admin/landing');
    }

    public function eliminarImagen(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 4]);
        $this->verifyCsrf();

        $db  = $this->db();
        $img = $db->fetch($db->query('SELECT ruta_archivo FROM CORE_Landing_Imagenes WHERE id_imagen=?', [[$id, SQLSRV_PARAM_IN]]));
        $db->query('DELETE FROM CORE_Landing_Imagenes WHERE id_imagen=?', [[$id, SQLSRV_PARAM_IN]]);

        if ($img) {
            $this->borrarArchivoSiPropio((string)$img['ruta_archivo']);
        }

        SessionHelper::flash('success', 'Imagen eliminada.');
        $this->redirect('/admin/landing');
    }

    // ─── Noticias (SIEMPRE con imagen — alimentan el carrusel visual) ──────────

    public function crearNoticia(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 2]);
        $this->verifyCsrf();

        $texto  = trim((string)($_POST['texto'] ?? ''));
        $enlace = trim((string)($_POST['enlace'] ?? ''));
        if ($texto === '') {
            SessionHelper::flash('error', 'La noticia no puede estar vacía.');
            $this->redirect('/admin/landing');
        }
        if ($enlace !== '' && !filter_var($enlace, FILTER_VALIDATE_URL)) {
            SessionHelper::flash('error', 'El enlace no es una URL válida (debe incluir https://).');
            $this->redirect('/admin/landing');
        }

        try {
            $imagenRuta = $this->guardarImagenSubida($_FILES['imagen'] ?? null);
        } catch (RuntimeException $e) {
            SessionHelper::flash('error', 'Una noticia necesita imagen: ' . $e->getMessage());
            $this->redirect('/admin/landing');
        }

        $db = $this->db();
        $maxOrden = (int)($db->fetch($db->query('SELECT ISNULL(MAX(orden), 0) AS m FROM CORE_Landing_Noticias'))['m'] ?? 0);
        $db->query(
            'INSERT INTO CORE_Landing_Noticias (texto, imagen, enlace, orden, estado, creado_por) VALUES (?,?,?,?,1,?)',
            [
                [mb_substr($texto, 0, 300), SQLSRV_PARAM_IN],
                [$imagenRuta, SQLSRV_PARAM_IN],
                [$enlace !== '' ? $enlace : null, SQLSRV_PARAM_IN],
                [$maxOrden + 1, SQLSRV_PARAM_IN],
                [(int)($_SESSION['user_id'] ?? 0), SQLSRV_PARAM_IN],
            ]
        );

        SessionHelper::flash('success', 'Noticia agregada al carrusel.');
        $this->redirect('/admin/landing');
    }

    public function actualizarNoticia(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 3]);
        $this->verifyCsrf();

        $texto  = trim((string)($_POST['texto'] ?? ''));
        $enlace = trim((string)($_POST['enlace'] ?? ''));
        if ($texto === '') {
            SessionHelper::flash('error', 'La noticia no puede estar vacía.');
            $this->redirect('/admin/landing');
        }
        if ($enlace !== '' && !filter_var($enlace, FILTER_VALIDATE_URL)) {
            SessionHelper::flash('error', 'El enlace no es una URL válida (debe incluir https://).');
            $this->redirect('/admin/landing');
        }

        $db     = $this->db();
        $actual = $db->fetch($db->query('SELECT imagen FROM CORE_Landing_Noticias WHERE id_noticia=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$actual) { $this->redirect('/admin/landing'); }
        $imagenRuta = $actual['imagen'];

        // La imagen es obligatoria en Noticias: solo se puede reemplazar, no quitar.
        if (!empty($_FILES['imagen']['name'])) {
            try {
                $nueva = $this->guardarImagenSubida($_FILES['imagen']);
            } catch (RuntimeException $e) {
                SessionHelper::flash('error', $e->getMessage());
                $this->redirect('/admin/landing');
            }
            $this->borrarArchivoSiPropio($imagenRuta);
            $imagenRuta = $nueva;
        }

        $db->query(
            'UPDATE CORE_Landing_Noticias SET texto=?, imagen=?, enlace=? WHERE id_noticia=?',
            [
                [mb_substr($texto, 0, 300), SQLSRV_PARAM_IN],
                [$imagenRuta, SQLSRV_PARAM_IN],
                [$enlace !== '' ? $enlace : null, SQLSRV_PARAM_IN],
                [$id, SQLSRV_PARAM_IN],
            ]
        );

        SessionHelper::flash('success', 'Noticia actualizada.');
        $this->redirect('/admin/landing');
    }

    public function toggleNoticia(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 3]);
        $this->verifyCsrf();

        $this->db()->query(
            'UPDATE CORE_Landing_Noticias SET estado = 1 - estado WHERE id_noticia = ?',
            [[$id, SQLSRV_PARAM_IN]]
        );
        $this->redirect('/admin/landing');
    }

    public function moverNoticia(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 3]);
        $this->verifyCsrf();

        $this->moverOrden('CORE_Landing_Noticias', 'id_noticia', $id, (string)($_POST['direccion'] ?? ''));
        $this->redirect('/admin/landing');
    }

    public function eliminarNoticia(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 4]);
        $this->verifyCsrf();

        $db = $this->db();
        $n  = $db->fetch($db->query('SELECT imagen FROM CORE_Landing_Noticias WHERE id_noticia=?', [[$id, SQLSRV_PARAM_IN]]));
        $db->query('DELETE FROM CORE_Landing_Noticias WHERE id_noticia=?', [[$id, SQLSRV_PARAM_IN]]);
        if ($n) {
            $this->borrarArchivoSiPropio($n['imagen'] ?? null);
        }

        SessionHelper::flash('success', 'Noticia eliminada.');
        $this->redirect('/admin/landing');
    }

    // ─── Consejos y novedades (texto puro, sin imagen — franja aparte) ─────────

    public function crearConsejo(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 2]);
        $this->verifyCsrf();

        $texto  = trim((string)($_POST['texto'] ?? ''));
        $enlace = trim((string)($_POST['enlace'] ?? ''));
        if ($texto === '') {
            SessionHelper::flash('error', 'El consejo no puede estar vacío.');
            $this->redirect('/admin/landing');
        }
        if ($enlace !== '' && !filter_var($enlace, FILTER_VALIDATE_URL)) {
            SessionHelper::flash('error', 'El enlace no es una URL válida (debe incluir https://).');
            $this->redirect('/admin/landing');
        }

        $db = $this->db();
        $maxOrden = (int)($db->fetch($db->query('SELECT ISNULL(MAX(orden), 0) AS m FROM CORE_Landing_Consejos'))['m'] ?? 0);
        $db->query(
            'INSERT INTO CORE_Landing_Consejos (texto, enlace, orden, estado, creado_por) VALUES (?,?,?,1,?)',
            [
                [mb_substr($texto, 0, 300), SQLSRV_PARAM_IN],
                [$enlace !== '' ? $enlace : null, SQLSRV_PARAM_IN],
                [$maxOrden + 1, SQLSRV_PARAM_IN],
                [(int)($_SESSION['user_id'] ?? 0), SQLSRV_PARAM_IN],
            ]
        );

        SessionHelper::flash('success', 'Consejo agregado.');
        $this->redirect('/admin/landing');
    }

    public function actualizarConsejo(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 3]);
        $this->verifyCsrf();

        $texto  = trim((string)($_POST['texto'] ?? ''));
        $enlace = trim((string)($_POST['enlace'] ?? ''));
        if ($texto === '') {
            SessionHelper::flash('error', 'El consejo no puede estar vacío.');
            $this->redirect('/admin/landing');
        }
        if ($enlace !== '' && !filter_var($enlace, FILTER_VALIDATE_URL)) {
            SessionHelper::flash('error', 'El enlace no es una URL válida (debe incluir https://).');
            $this->redirect('/admin/landing');
        }

        $this->db()->query(
            'UPDATE CORE_Landing_Consejos SET texto=?, enlace=? WHERE id_consejo=?',
            [
                [mb_substr($texto, 0, 300), SQLSRV_PARAM_IN],
                [$enlace !== '' ? $enlace : null, SQLSRV_PARAM_IN],
                [$id, SQLSRV_PARAM_IN],
            ]
        );

        SessionHelper::flash('success', 'Consejo actualizado.');
        $this->redirect('/admin/landing');
    }

    public function toggleConsejo(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 3]);
        $this->verifyCsrf();

        $this->db()->query(
            'UPDATE CORE_Landing_Consejos SET estado = 1 - estado WHERE id_consejo = ?',
            [[$id, SQLSRV_PARAM_IN]]
        );
        $this->redirect('/admin/landing');
    }

    public function moverConsejo(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 3]);
        $this->verifyCsrf();

        $this->moverOrden('CORE_Landing_Consejos', 'id_consejo', $id, (string)($_POST['direccion'] ?? ''));
        $this->redirect('/admin/landing');
    }

    public function eliminarConsejo(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_LANDING, 4]);
        $this->verifyCsrf();

        $this->db()->query('DELETE FROM CORE_Landing_Consejos WHERE id_consejo=?', [[$id, SQLSRV_PARAM_IN]]);
        SessionHelper::flash('success', 'Consejo eliminado.');
        $this->redirect('/admin/landing');
    }

    // ─── Helpers compartidos ────────────────────────────────────────────────────

    /**
     * Valida y guarda una imagen subida por $_FILES en CARPETA_UPLOAD.
     * Devuelve la ruta relativa guardada o lanza RuntimeException con un
     * mensaje apto para mostrar al usuario.
     */
    private function guardarImagenSubida(?array $archivo): string {
        if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir la imagen (revisa el tamaño/formato).');
        }
        if ($archivo['size'] > self::TAM_MAX_BYTES) {
            throw new RuntimeException('La imagen supera el máximo de 5 MB.');
        }

        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::EXT_PERMITIDAS, true)) {
            throw new RuntimeException('Formato no permitido. Usa JPG, PNG o WEBP.');
        }

        // Verificación real del contenido (no solo la extensión declarada).
        if (@getimagesize($archivo['tmp_name']) === false) {
            throw new RuntimeException('El archivo no es una imagen válida.');
        }

        $carpetaAbs = ROOT . '/' . self::CARPETA_UPLOAD;
        if (!is_dir($carpetaAbs)) {
            mkdir($carpetaAbs, 0755, true);
        }

        $nombre = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($archivo['tmp_name'], $carpetaAbs . '/' . $nombre)) {
            throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
        }

        return self::CARPETA_UPLOAD . '/' . $nombre;
    }

    /** Borra un archivo solo si vive dentro de la carpeta de uploads del carrusel. */
    private function borrarArchivoSiPropio(?string $rutaRelativa): void {
        if (!$rutaRelativa) return;
        $ruta = ROOT . '/' . ltrim($rutaRelativa, '/');
        $base = str_replace('\\', '/', ROOT . '/' . self::CARPETA_UPLOAD);
        if (str_starts_with(str_replace('\\', '/', $ruta), $base) && is_file($ruta)) {
            @unlink($ruta);
        }
    }

    /** Intercambia el `orden` de una fila con su vecino inmediato (arriba/abajo). */
    private function moverOrden(string $tabla, string $pk, int $id, string $direccion): void {
        if (!in_array($direccion, ['arriba', 'abajo'], true)) return;
        $db  = $this->db();
        $fila = $db->fetch($db->query("SELECT $pk, orden FROM $tabla WHERE $pk=?", [[$id, SQLSRV_PARAM_IN]]));
        if (!$fila) return;

        $cmp = $direccion === 'arriba' ? '<' : '>';
        $ord = $direccion === 'arriba' ? 'DESC' : 'ASC';
        $vecino = $db->fetch($db->query(
            "SELECT TOP 1 $pk, orden FROM $tabla WHERE orden $cmp ? ORDER BY orden $ord",
            [[(int)$fila['orden'], SQLSRV_PARAM_IN]]
        ));
        if (!$vecino) return;

        $db->query("UPDATE $tabla SET orden=? WHERE $pk=?", [[(int)$vecino['orden'], SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN]]);
        $db->query("UPDATE $tabla SET orden=? WHERE $pk=?", [[(int)$fila['orden'], SQLSRV_PARAM_IN], [(int)$vecino[$pk], SQLSRV_PARAM_IN]]);
    }
}
