<?php
/**
 * Vista: formulario de nuevo Tercero (proceso 11).
 * Campos obligatorios según el diccionario; el RNDC hereda el resto.
 */
declare(strict_types=1);

$tiposId = [
    'C' => 'C - Cédula de ciudadanía',
    'N' => 'N - NIT',
    'E' => 'E - Cédula de extranjería',
    'T' => 'T - Tarjeta de identidad',
    'P' => 'P - Pasaporte',
];
?>
<!-- Leaflet (mapa) solo en esta página -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<h1>Nuevo Tercero</h1>
<p class="ayuda">Remitentes, destinatarios, propietarios, tenedores y conductores.
   Se registra una vez; luego el RNDC hereda sus datos en remesas y manifiestos.</p>

<?php flash(); ?>

<form method="post" action="<?= e(ruta('tercero.crear')) ?>" class="form">

    <fieldset>
        <legend>Datos generales</legend>
        <div class="grid">
            <label>Tipo de identificación *
                <select name="tipo_id" required>
                    <?php foreach ($tiposId as $v => $t): ?>
                        <option value="<?= e($v) ?>"><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Número de identificación *
                <input type="text" name="num_id" maxlength="15" required>
            </label>
            <label class="ancho-total">Nombre o razón social *
                <input type="text" name="nombre" maxlength="100" required>
            </label>
            <label>Primer apellido
                <input type="text" name="primer_apellido" maxlength="100">
            </label>
            <label>Segundo apellido
                <input type="text" name="segundo_apellido" maxlength="100">
            </label>
            <label>Régimen simple (S/N)
                <input type="text" name="regimen_simple" maxlength="1" placeholder="N">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Ubicación</legend>
        <div class="grid">
            <label class="ancho-total">Municipio *
                <div class="autocompletar" data-ac="municipios">
                    <input type="text" class="ac-texto" name="municipio_nombre" autocomplete="off" placeholder="Escribe y elige…" required>
                    <ul class="ac-lista"></ul>
                    <input type="hidden" name="cod_municipio" data-ac-field="codigo_rndc">
                </div>
            </label>
            <label class="ancho-total">Dirección *
                <input type="text" name="direccion" maxlength="120" required>
            </label>
            <label>Sede
                <input type="text" name="sede" maxlength="6" placeholder="0">
            </label>
            <label>Nombre de la sede
                <input type="text" name="nombre_sede" maxlength="40">
            </label>
            <label>Teléfono
                <input type="text" name="telefono" maxlength="10">
            </label>
            <label>Celular
                <input type="text" name="celular" maxlength="10">
            </label>
            <label class="ancho-total">Correo
                <input type="email" name="email" maxlength="120">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Ubicación geográfica (latitud / longitud)</legend>
        <div class="mapa-barra">
            <input type="text" id="mapa-buscar" placeholder="Buscar dirección o lugar…">
            <button type="button" id="mapa-buscar-btn" class="btn">Buscar</button>
            <a href="#" id="abrir-google-maps" class="btn">Abrir en Google Maps</a>
        </div>
        <div class="mapa-barra">
            <input type="text" id="mapa-pegar" placeholder="Pega aquí un enlace de Google Maps (con @lat,lng) o escribe lat,lng">
            <button type="button" id="mapa-pegar-btn" class="btn">Usar enlace</button>
        </div>
        <div id="mapa"></div>
        <p class="ayuda">Haz clic en el mapa o arrastra el marcador para fijar la ubicación.</p>
        <div class="grid">
            <label>Latitud
                <input type="text" id="latitud" name="latitud" readonly placeholder="(clic en el mapa)">
            </label>
            <label>Longitud
                <input type="text" id="longitud" name="longitud" readonly placeholder="(clic en el mapa)">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>¿Es conductor?</legend>
        <label class="check">
            <input type="checkbox" name="es_conductor" value="1" id="chk-conductor"> Sí, este tercero es conductor
        </label>
        <div class="grid" id="campos-conductor" style="margin-top:10px;">
            <label>Categoría licencia
                <input type="text" name="categoria_licencia" maxlength="3">
            </label>
            <label>Número licencia
                <input type="text" name="num_licencia" maxlength="20">
            </label>
            <label>Vencimiento licencia
                <input type="date" name="fecha_venc_licencia">
            </label>
        </div>
    </fieldset>

    <div class="acciones">
        <button type="submit" class="btn btn--primario">Guardar tercero</button>
        <a href="<?= e(ruta('terceros')) ?>" class="btn">Cancelar</a>
    </div>
</form>

<script>
/* Muestra/oculta los campos de conductor */
document.addEventListener('DOMContentLoaded', function () {
    var chk = document.getElementById('chk-conductor');
    var cc = document.getElementById('campos-conductor');
    function tog(){ cc.style.display = chk.checked ? '' : 'none'; }
    if (chk) { chk.addEventListener('change', tog); tog(); }
});
</script>
