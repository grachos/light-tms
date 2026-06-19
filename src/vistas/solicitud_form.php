<?php
/**
 * Vista: formulario de nueva Solicitud de Servicio (replanteado).
 * Captura todo lo necesario para sembrar una Remesa y un Manifiesto válidos.
 */
declare(strict_types=1);

$tiposId = [
    'C' => 'C - Cédula', 'N' => 'N - NIT', 'E' => 'E - C. extranjería',
    'T' => 'T - T. identidad', 'P' => 'P - Pasaporte',
];
$responsables = ['E' => 'E - Empresa', 'R' => 'R - Remitente', 'D' => 'D - Destinatario'];
$empaques = (new CatalogoRepo())->empaques();

if (!function_exists('selOpc')) {
    function selOpc(string $name, array $opciones, string $def = '', bool $conVacio = false): string
    {
        $h = '<select name="' . e($name) . '">';
        if ($conVacio) { $h .= '<option value="">—</option>'; }
        foreach ($opciones as $v => $t) {
            $h .= '<option value="' . e((string) $v) . '"' . ((string) $v === $def ? ' selected' : '') . '>' . e($t) . '</option>';
        }
        return $h . '</select>';
    }
}
if (!function_exists('acTercero')) {
    // Picker de tercero: llena dos hidden (tipo/num). $params p.ej. 'solo_conductor=1'
    function acTercero(string $tipoName, string $numName, string $ph = 'Buscar tercero…', string $params = ''): string
    {
        $p = $params !== '' ? ' data-ac-params="' . e($params) . '"' : '';
        return '<div class="autocompletar" data-ac="terceros"' . $p . '>'
            . '<input type="text" class="ac-texto" autocomplete="off" placeholder="' . e($ph) . '">'
            . '<ul class="ac-lista"></ul>'
            . '<input type="hidden" name="' . e($tipoName) . '" data-ac-field="tipo_id">'
            . '<input type="hidden" name="' . e($numName) . '" data-ac-field="num_id">'
            . '</div>';
    }
}
if (!function_exists('acMunicipio')) {
    function acMunicipio(string $name, string $ph = 'Escribe y elige…'): string
    {
        return '<div class="autocompletar" data-ac="municipios">'
            . '<input type="text" class="ac-texto" autocomplete="off" placeholder="' . e($ph) . '">'
            . '<ul class="ac-lista"></ul>'
            . '<input type="hidden" name="' . e($name) . '" data-ac-field="codigo_rndc">'
            . '</div>';
    }
}
?>
<h1>Nueva Solicitud de Servicio</h1>
<p class="ayuda">Se captura una vez y genera automáticamente el <strong>Manifiesto</strong> y la
   <strong>Remesa</strong>. Las partes, el vehículo y los municipios se eligen de los maestros.</p>

<?php flash(); ?>

<form method="post" action="<?= e(ruta('solicitud.crear')) ?>" class="form">

    <fieldset>
        <legend>1. Generales</legend>
        <div class="grid">
            <label>Consecutivo <input type="text" name="consecutivo" maxlength="30" placeholder="SS-0001"></label>
            <label>Fecha <input type="date" name="fecha_solicitud" value="<?= e(date('Y-m-d')) ?>"></label>
            <label>Operación de transporte <input type="text" name="operacion_transporte" maxlength="2" placeholder="G"></label>
            <label>Tipo de viaje <?= selOpc('tipo_viaje', ['NACIONAL' => 'Nacional', 'URBANO' => 'Urbano'], 'NACIONAL') ?></label>
            <label>¿Sube al RNDC? <?= selOpc('sube_rndc', ['1' => 'Sí', '0' => 'No'], '1') ?></label>
            <label class="ancho-total">Observaciones <textarea name="observaciones" rows="2" maxlength="200"></textarea></label>
        </div>
    </fieldset>

    <fieldset>
        <legend>2. Partes</legend>
        <div class="grid">
            <label>Tipo id. empresa <?= selOpc('empresa_tipo_id', $tiposId, 'N') ?></label>
            <label>NIT empresa <input type="text" name="empresa_num_id" maxlength="20" value="<?= e(config()['rndc']['empresa']) ?>"></label>
            <label class="ancho-total">Remitente <?= acTercero('remitente_tipo_id', 'remitente_num_id') ?></label>
            <label class="ancho-total">Destinatario <?= acTercero('destinatario_tipo_id', 'destinatario_num_id') ?></label>
            <label class="ancho-total">Propietario de la carga <?= acTercero('propietario_carga_tipo_id', 'propietario_carga_num_id') ?></label>
            <label class="ancho-total">Titular del manifiesto (propietario/tenedor del vehículo) <?= acTercero('titular_tipo_id', 'titular_num_id') ?></label>
            <label class="ancho-total">Conductor <?= acTercero('conductor_tipo_id', 'conductor_num_id', 'Buscar conductor…', 'solo_conductor=1') ?></label>
            <label class="ancho-total">Vehículo (placa)
                <div class="autocompletar" data-ac="vehiculos">
                    <input type="text" class="ac-texto" autocomplete="off" placeholder="Buscar placa…">
                    <ul class="ac-lista"></ul>
                    <input type="hidden" name="placa_vehiculo" data-ac-field="placa">
                </div>
            </label>
        </div>
        <p class="ayuda">¿Falta alguien o el vehículo? Créalos en
           <a href="<?= e(ruta('terceros')) ?>" target="_blank">Terceros</a> /
           <a href="<?= e(ruta('vehiculos')) ?>" target="_blank">Vehículos</a>.</p>
    </fieldset>

    <fieldset>
        <legend>3. Ruta</legend>
        <div class="grid">
            <label class="ancho-total">Municipio origen <?= acMunicipio('municipio_origen') ?></label>
            <label class="ancho-total">Municipio destino <?= acMunicipio('municipio_destino') ?></label>
            <label class="ancho-total">Municipio pago del saldo <?= acMunicipio('municipio_pago_saldo') ?></label>
        </div>
    </fieldset>

    <fieldset>
        <legend>4. Carga</legend>
        <div class="grid">
            <label>Naturaleza de la carga <input type="text" name="naturaleza_carga" maxlength="2" placeholder="1"></label>
            <label>Tipo de empaque
                <select name="tipo_empaque">
                    <option value="">—</option>
                    <?php foreach ($empaques as $emp): ?>
                        <option value="<?= e($emp['codigo']) ?>"><?= e($emp['codigo'] . ' - ' . $emp['descripcion']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="ancho-total">Producto / mercancía (catálogo o código libre)
                <div class="autocompletar" data-ac="productos">
                    <input type="text" class="ac-texto" autocomplete="off" placeholder="Buscar producto en el catálogo…">
                    <ul class="ac-lista"></ul>
                    <input type="text" name="mercancia_codigo" data-ac-field="codigo" maxlength="10" placeholder="Código (autollenado o manual)">
                </div>
            </label>
            <label class="ancho-total">Descripción del producto <input type="text" name="descripcion_producto" maxlength="250"></label>
            <label>Cantidad cargada <input type="number" step="0.001" name="cantidad_cargada"></label>
            <label>Unidad de medida <input type="text" name="unidad_medida" maxlength="2" placeholder="1"></label>
            <label>Peso (kg) <input type="number" step="0.001" name="peso"></label>
            <label>Valor de la mercancía <input type="number" step="0.01" name="valor_mercancia"></label>
        </div>
    </fieldset>

    <fieldset>
        <legend>5. Cargue / Descargue</legend>
        <div class="grid">
            <label>Fecha cita cargue <input type="date" name="fecha_cita_cargue"></label>
            <label>Hora cita cargue <input type="time" name="hora_cita_cargue"></label>
            <label>Fecha cita descargue <input type="date" name="fecha_cita_descargue"></label>
            <label>Hora cita descargue <input type="time" name="hora_cita_descargue"></label>
            <label>Tiempo descargue: horas <input type="number" name="horas_pacto_descargue" placeholder="0"></label>
            <label>Tiempo descargue: minutos <input type="number" name="minutos_pacto_descargue" placeholder="0"></label>
            <label class="ancho-total">Dirección de cargue <input type="text" name="direccion_cargue" maxlength="120"></label>
            <label class="ancho-total">Dirección de descargue <input type="text" name="direccion_descargue" maxlength="120"></label>
            <label>Responsable pago cargue <?= selOpc('responsable_pago_cargue', $responsables, 'E') ?></label>
            <label>Responsable pago descargue <?= selOpc('responsable_pago_descargue', $responsables, 'E') ?></label>
        </div>
    </fieldset>

    <fieldset>
        <legend>6. Valores y manifiesto</legend>
        <div class="grid">
            <label>Valor del flete <input type="number" step="0.01" name="valor_flete"></label>
            <label>Valor del anticipo <input type="number" step="0.01" name="valor_anticipo"></label>
            <label>Retención ICA ($/mil) <input type="number" step="0.01" name="retencion_ica"></label>
            <label>Tipo de flete <input type="text" name="tipo_flete" maxlength="2"></label>
            <label>Fecha pago del saldo <input type="date" name="fecha_pago_saldo"></label>
            <label>Nro. póliza <input type="text" name="nro_poliza" maxlength="20"></label>
            <label>Tomador de la póliza <?= selOpc('tomador_poliza', $responsables, 'E') ?></label>
        </div>
    </fieldset>

    <div class="acciones">
        <button type="submit" class="btn btn--primario">Guardar solicitud</button>
        <a href="<?= e(ruta('solicitudes')) ?>" class="btn">Cancelar</a>
    </div>
</form>
