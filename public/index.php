<?php
/**
 * Light TMS - Front controller.
 * Enruta las páginas mediante ?r=<ruta>.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/vista.php';
require_once __DIR__ . '/../src/Solicitud/SolicitudRepo.php';

$r = $_GET['r'] ?? 'inicio';

try {
    switch ($r) {
        case 'solicitudes':
            $repo = new SolicitudRepo();
            $solicitudes = $repo->listar();
            layout_top('Solicitudes', 'solicitudes');
            require __DIR__ . '/../src/vistas/solicitudes.php';
            layout_bottom();
            break;

        case 'solicitud.nueva':
            layout_top('Nueva solicitud', 'solicitud.nueva');
            require __DIR__ . '/../src/vistas/solicitud_form.php';
            layout_bottom();
            break;

        case 'solicitud.crear':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . ruta('solicitud.nueva'));
                break;
            }
            $repo = new SolicitudRepo();
            try {
                $id = $repo->crear($_POST);
                header('Location: ' . ruta('solicitud.ver', [
                    'id' => $id,
                    'ok' => 'Solicitud creada. Manifiesto y Remesa generados.',
                ]));
            } catch (Throwable $e) {
                $msg = config()['app']['debug'] ? $e->getMessage() : 'No se pudo guardar la solicitud.';
                header('Location: ' . ruta('solicitud.nueva', ['err' => $msg]));
            }
            break;

        case 'solicitud.ver':
            $id = (int) ($_GET['id'] ?? 0);
            $repo = new SolicitudRepo();
            $datos = $repo->obtener($id);
            if ($datos === null) {
                http_response_code(404);
                layout_top('No encontrada', 'solicitudes');
                echo '<div class="tarjeta vacio">Solicitud no encontrada.</div>';
                layout_bottom();
                break;
            }
            $solicitud  = $datos['solicitud'];
            $manifiesto = $datos['manifiesto'];
            $remesa     = $datos['remesa'];
            layout_top('Solicitud #' . $id, 'solicitudes');
            require __DIR__ . '/../src/vistas/solicitud_detalle.php';
            layout_bottom();
            break;

        case 'inicio':
        default:
            require __DIR__ . '/../src/vistas/inicio.php';
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    layout_top('Error', '');
    echo '<div class="alerta alerta--err">Ocurrió un error.';
    if (config()['app']['debug']) {
        echo '<br><small>' . e($e->getMessage()) . '</small>';
    }
    echo '</div>';
    layout_bottom();
}
