<?php
/**
 * Vista: formulario de nueva Solicitud de Servicio.
 * Captura los datos base que sembrarán Manifiesto + Remesa.
 */
declare(strict_types=1);

/** Opciones de tipo de identificación (RNDC). */
$tiposId = [
    'C' => 'C - Cédula de ciudadanía',
    'N' => 'N - NIT',
    'E' => 'E - Cédula de extranjería',
    'T' => 'T - Tarjeta de identidad',
    'P' => 'P - Pasaporte',
];

if (!function_exists('selectTipoId')) {
    /** Helper local: select de tipo de identificación. */
    function selectTipoId(string $name, array $opciones, string $def): string
    {
        $html = '<select name="' . e($name) . '">';
        foreach ($opciones as $val => $etq) {
            $sel = $val === $def ? ' selected' : '';
            $html .= '<option value="' . e($val) . '"' . $sel . '>' . e($etq) . '</option>';
        }
        return $html . '</select>';
    }
}
?>
<h1>Nueva Solicitud de Servicio</h1>
<p class="ayuda">Se captura una sola vez. Al guardar se generan automáticamente el
   <strong>Manifiesto</strong> y la <strong>Remesa</strong>.</p>

<form method="post" action="<?= e(ruta('solicitud.crear')) ?>" class="form">

    <fieldset>
        <legend>Datos generales</legend>
        <div class="grid">
            <label>Consecutivo interno
                <input type="text" name="consecutivo" maxlength="30" placeholder="p. ej. SS-0001">
            </label>
            <label>Fecha de solicitud
                <input type="date" name="fecha_solicitud" value="<?= e(date('Y-m-d')) ?>">
            </label>
            <label>Operación de transporte
                <input type="text" name="operacion_transporte" maxlength="2" placeholder="G">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Ruta</legend>
        <div class="grid">
            <label>Municipio origen (DIVIPOLA)
                <input type="text" name="municipio_origen" maxlength="8" placeholder="76001000">
            </label>
            <label>Municipio destino (DIVIPOLA)
                <input type="text" name="municipio_destino" maxlength="8" placeholder="11001000">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Empresa, vehículo y conductor</legend>
        <div class="grid">
            <label>Tipo id. empresa
                <?= selectTipoId('empresa_tipo_id', $tiposId, 'N') ?>
            </label>
            <label>NIT empresa transportadora
                <input type="text" name="empresa_num_id" maxlength="20" value="<?= e(config()['rndc']['empresa']) ?>">
            </label>
            <label>Placa vehículo
                <input type="text" name="placa_vehiculo" maxlength="10" placeholder="ABC123">
            </label>
            <label>Tipo id. conductor
                <?= selectTipoId('conductor_tipo_id', $tiposId, 'C') ?>
            </label>
            <label>Número id. conductor
                <input type="text" name="conductor_num_id" maxlength="20">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Remitente y destinatario</legend>
        <div class="grid">
            <label>Tipo id. remitente
                <?= selectTipoId('remitente_tipo_id', $tiposId, 'N') ?>
            </label>
            <label>Número id. remitente
                <input type="text" name="remitente_num_id" maxlength="20">
            </label>
            <label>Tipo id. destinatario
                <?= selectTipoId('destinatario_tipo_id', $tiposId, 'N') ?>
            </label>
            <label>Número id. destinatario
                <input type="text" name="destinatario_num_id" maxlength="20">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Carga</legend>
        <div class="grid">
            <label>Naturaleza de la carga
                <input type="text" name="naturaleza_carga" maxlength="2" placeholder="1">
            </label>
            <label>Cantidad cargada
                <input type="number" step="0.001" name="cantidad_cargada" placeholder="0">
            </label>
            <label>Unidad de medida
                <input type="text" name="unidad_medida" maxlength="2" placeholder="1">
            </label>
            <label class="ancho-total">Descripción del producto
                <input type="text" name="descripcion_producto" maxlength="250">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Valores</legend>
        <div class="grid">
            <label>Valor del flete
                <input type="number" step="0.01" name="valor_flete" placeholder="0">
            </label>
            <label>Valor del anticipo
                <input type="number" step="0.01" name="valor_anticipo" placeholder="0">
            </label>
            <label class="ancho-total">Observaciones
                <textarea name="observaciones" rows="2" maxlength="200"></textarea>
            </label>
        </div>
    </fieldset>

    <div class="acciones">
        <button type="submit" class="btn btn--primario">Guardar solicitud</button>
        <a href="<?= e(ruta('solicitudes')) ?>" class="btn">Cancelar</a>
    </div>
</form>
