<?php
/**
 * Light TMS - Front controller.
 * Enruta las páginas mediante ?r=<ruta>.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/vista.php';
require_once __DIR__ . '/../src/Solicitud/SolicitudRepo.php';
require_once __DIR__ . '/../src/Maestro/MunicipioRepo.php';
require_once __DIR__ . '/../src/Maestro/TerceroRepo.php';
require_once __DIR__ . '/../src/Maestro/VehiculoRepo.php';
require_once __DIR__ . '/../src/Maestro/CatalogoRepo.php';
require_once __DIR__ . '/../src/Maestro/EmpresaRepo.php';

$r = $_GET['r'] ?? 'inicio';

try {
    switch ($r) {

        case 'municipios.buscar':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode((new MunicipioRepo())->buscar((string) ($_GET['q'] ?? '')), JSON_UNESCAPED_UNICODE);
            break;

        case 'terceros.buscar':
            header('Content-Type: application/json; charset=utf-8');
            $solo = !empty($_GET['solo_conductor']);
            echo json_encode((new TerceroRepo())->buscar((string) ($_GET['q'] ?? ''), $solo), JSON_UNESCAPED_UNICODE);
            break;

        case 'vehiculos.buscar':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode((new VehiculoRepo())->buscar((string) ($_GET['q'] ?? '')), JSON_UNESCAPED_UNICODE);
            break;

        case 'productos.buscar':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode((new CatalogoRepo())->buscarProductos((string) ($_GET['q'] ?? '')), JSON_UNESCAPED_UNICODE);
            break;

        case 'terceros':
            $terceros = (new TerceroRepo())->listar();
            layout_top('Terceros', 'terceros');
            require __DIR__ . '/../src/vistas/terceros.php';
            layout_bottom();
            break;

        case 'tercero.nuevo':
            layout_top('Nuevo tercero', 'terceros');
            require __DIR__ . '/../src/vistas/tercero_form.php';
            layout_bottom();
            break;

        case 'tercero.crear':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . ruta('tercero.nuevo'));
                break;
            }
            if (empty($_POST['cod_municipio'])) {
                header('Location: ' . ruta('tercero.nuevo', ['err' => 'Elige el municipio de la lista.']));
                break;
            }
            try {
                (new TerceroRepo())->crear($_POST);
                header('Location: ' . ruta('terceros', ['ok' => 'Tercero guardado.']));
            } catch (Throwable $e) {
                $msg = config()['app']['debug'] ? $e->getMessage() : 'No se pudo guardar el tercero.';
                header('Location: ' . ruta('tercero.nuevo', ['err' => $msg]));
            }
            break;

        case 'tercero.editar':
            $tercero = (new TerceroRepo())->obtener((int) ($_GET['id'] ?? 0));
            if ($tercero === null) {
                header('Location: ' . ruta('terceros', ['err' => 'Tercero no encontrado.']));
                break;
            }
            $accion = ruta('tercero.actualizar', ['id' => (int) $tercero['id']]);
            layout_top('Editar tercero', 'terceros');
            require __DIR__ . '/../src/vistas/tercero_form.php';
            layout_bottom();
            break;

        case 'tercero.actualizar':
            $id = (int) ($_GET['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . ruta('tercero.editar', ['id' => $id]));
                break;
            }
            if (empty($_POST['cod_municipio'])) {
                header('Location: ' . ruta('tercero.editar', ['id' => $id, 'err' => 'Elige el municipio de la lista.']));
                break;
            }
            try {
                (new TerceroRepo())->actualizar($id, $_POST);
                header('Location: ' . ruta('terceros', ['ok' => 'Tercero actualizado.']));
            } catch (Throwable $e) {
                $msg = config()['app']['debug'] ? $e->getMessage() : 'No se pudo actualizar.';
                header('Location: ' . ruta('tercero.editar', ['id' => $id, 'err' => $msg]));
            }
            break;

        case 'tercero.registrar':
            $id = (int) ($_GET['id'] ?? 0);
            $resp = (new TerceroRepo())->registrarEnRndc($id);
            if ($resp->ok) {
                header('Location: ' . ruta('terceros', ['ok' => 'Tercero registrado en RNDC (id ' . $resp->ingresoId . ').']));
            } else {
                header('Location: ' . ruta('terceros', ['err' => 'RNDC: ' . $resp->error]));
            }
            break;

        case 'vehiculos':
            $vehiculos = (new VehiculoRepo())->listar();
            layout_top('Vehículos', 'vehiculos');
            require __DIR__ . '/../src/vistas/vehiculos.php';
            layout_bottom();
            break;

        case 'vehiculo.nuevo':
            layout_top('Nuevo vehículo', 'vehiculos');
            require __DIR__ . '/../src/vistas/vehiculo_form.php';
            layout_bottom();
            break;

        case 'vehiculo.crear':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . ruta('vehiculo.nuevo'));
                break;
            }
            if (empty($_POST['tenedor_num_id'])) {
                header('Location: ' . ruta('vehiculo.nuevo', ['err' => 'Elige el tenedor de la lista de terceros.']));
                break;
            }
            try {
                (new VehiculoRepo())->crear($_POST);
                header('Location: ' . ruta('vehiculos', ['ok' => 'Vehículo guardado.']));
            } catch (Throwable $e) {
                $msg = config()['app']['debug'] ? $e->getMessage() : 'No se pudo guardar el vehículo.';
                header('Location: ' . ruta('vehiculo.nuevo', ['err' => $msg]));
            }
            break;

        case 'vehiculo.editar':
            $vehiculo = (new VehiculoRepo())->obtener((int) ($_GET['id'] ?? 0));
            if ($vehiculo === null) {
                header('Location: ' . ruta('vehiculos', ['err' => 'Vehículo no encontrado.']));
                break;
            }
            $accion = ruta('vehiculo.actualizar', ['id' => (int) $vehiculo['id']]);
            layout_top('Editar vehículo', 'vehiculos');
            require __DIR__ . '/../src/vistas/vehiculo_form.php';
            layout_bottom();
            break;

        case 'vehiculo.actualizar':
            $id = (int) ($_GET['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . ruta('vehiculo.editar', ['id' => $id]));
                break;
            }
            if (empty($_POST['tenedor_num_id'])) {
                header('Location: ' . ruta('vehiculo.editar', ['id' => $id, 'err' => 'Elige el tenedor de la lista de terceros.']));
                break;
            }
            try {
                (new VehiculoRepo())->actualizar($id, $_POST);
                header('Location: ' . ruta('vehiculos', ['ok' => 'Vehículo actualizado.']));
            } catch (Throwable $e) {
                $msg = config()['app']['debug'] ? $e->getMessage() : 'No se pudo actualizar.';
                header('Location: ' . ruta('vehiculo.editar', ['id' => $id, 'err' => $msg]));
            }
            break;

        case 'vehiculo.registrar':
            $id = (int) ($_GET['id'] ?? 0);
            $resp = (new VehiculoRepo())->registrarEnRndc($id);
            if ($resp->ok) {
                header('Location: ' . ruta('vehiculos', ['ok' => 'Vehículo registrado en RNDC (id ' . $resp->ingresoId . ').']));
            } else {
                header('Location: ' . ruta('vehiculos', ['err' => 'RNDC: ' . $resp->error]));
            }
            break;
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

        case 'empresa':
            $empresa = (new EmpresaRepo())->obtener();
            layout_top('Empresa', 'empresa');
            require __DIR__ . '/../src/vistas/empresa_form.php';
            layout_bottom();
            break;

        case 'empresa.guardar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                (new EmpresaRepo())->guardar($_POST);
            }
            header('Location: ' . ruta('empresa', ['ok' => 'Datos de la empresa guardados.']));
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
