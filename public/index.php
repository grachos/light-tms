<?php
/**
 * Light TMS - Tablero de inicio (Fase 1).
 *
 * Verifica la conexión a la base de datos y muestra el estado del proyecto.
 * Es el punto de entrada web (document root recomendado: esta carpeta /public).
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$cfg     = config();
$errorDb = null;
$bdOk    = db_disponible($errorDb);

$conteos = [
    'Solicitudes de servicio' => $bdOk ? contar_tabla('solicitud_servicio') : null,
    'Manifiestos'             => $bdOk ? contar_tabla('manifiesto') : null,
    'Remesas'                 => $bdOk ? contar_tabla('remesa') : null,
    'En cola (RNDC)'          => $bdOk ? contar_tabla('cola_envios') : null,
];
$esquemaOk = $bdOk && !in_array(null, $conteos, true);
?>
<!DOCTYPE html>
<html lang="es-CO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($cfg['app']['name']) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="barra">
        <div class="barra__marca"><?= e($cfg['app']['name']) ?></div>
        <div class="barra__sub">Mini TMS &middot; RNDC Colombia</div>
    </header>

    <main class="contenido">
        <section class="tarjeta">
            <h1>Estado del sistema</h1>

            <div class="estado <?= $bdOk ? 'estado--ok' : 'estado--error' ?>">
                <span class="estado__punto"></span>
                <?php if ($bdOk): ?>
                    Base de datos conectada
                    (<?= e($cfg['db']['name']) ?> en <?= e($cfg['db']['host']) ?>)
                <?php else: ?>
                    Sin conexión a la base de datos
                <?php endif; ?>
            </div>

            <?php if (!$bdOk): ?>
                <p class="ayuda">
                    Revisa las credenciales en el archivo <code>.env</code>.
                    <?php if ($cfg['app']['debug'] && $errorDb): ?>
                        <br><small>Detalle: <?= e($errorDb) ?></small>
                    <?php endif; ?>
                </p>
            <?php elseif (!$esquemaOk): ?>
                <p class="ayuda">
                    La base de datos responde, pero faltan tablas.
                    Importa <code>sql/schema.sql</code> desde phpMyAdmin.
                </p>
            <?php endif; ?>
        </section>

        <section class="tarjeta">
            <h2>Documentos</h2>
            <div class="contadores">
                <?php foreach ($conteos as $titulo => $valor): ?>
                    <div class="contador">
                        <div class="contador__num"><?= $valor === null ? '—' : (int) $valor ?></div>
                        <div class="contador__lbl"><?= e($titulo) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tarjeta">
            <h2>Próximos pasos</h2>
            <ol class="pasos">
                <li class="<?= $esquemaOk ? 'hecho' : '' ?>">Fase 1 — Esqueleto + esquema de BD</li>
                <li class="hecho">Fase 2 — Cliente RNDC (SOAP/XML + acceso)</li>
                <li>Fase 3 — Flujo Solicitud de Servicio (siembra Manifiesto + Remesa)</li>
                <li>Fase 4 — Worker de reintento (cron / store-and-forward)</li>
            </ol>
        </section>
    </main>

    <footer class="pie">
        <?= e($cfg['app']['name']) ?> &middot; entorno: <?= e($cfg['app']['env']) ?>
    </footer>
</body>
</html>
