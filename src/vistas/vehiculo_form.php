<?php
/**
 * Vista: formulario de Vehículo (crear o editar, proceso 12).
 * @var array<string,mixed> $vehiculo (vacío al crear)
 * @var string $accion
 */
declare(strict_types=1);

$vh     = $vehiculo ?? [];
$accion = $accion ?? ruta('vehiculo.crear');
$editar = !empty($vh);
$val    = static fn (string $c): string => e((string) ($GLOBALS['vh'][$c] ?? ''));

/** Texto inicial del picker de tercero al editar (tipo + número). */
$tenedorTxt = $editar && !empty($vh['tenedor_num_id']) ? trim(($vh['tenedor_tipo_id'] ?? '') . ' ' . $vh['tenedor_num_id']) : '';
$propTxt    = $editar && !empty($vh['propietario_num_id']) ? trim(($vh['propietario_tipo_id'] ?? '') . ' ' . $vh['propietario_num_id']) : '';
?>
<h1><?= $editar ? 'Editar' : 'Nuevo' ?> Vehículo</h1>
<p class="ayuda">Mínimo: placa, configuración, peso vacío y tenedor.
   Marca, modelo y propietario son opcionales (el RNDC los hereda del RUNT por placa).</p>

<?php flash(); ?>

<form method="post" action="<?= e($accion) ?>" class="form">

    <fieldset>
        <legend>Características generales</legend>
        <div class="grid">
            <label>Placa * <input type="text" name="placa" maxlength="6" required style="text-transform:uppercase" value="<?= $val('placa') ?>"></label>
            <label>Configuración * <input type="text" name="cod_configuracion" maxlength="2" required placeholder="2, 3, 55" value="<?= $val('cod_configuracion') ?>"></label>
            <label>Peso vacío (kg) * <input type="number" name="peso_vacio" required value="<?= $val('peso_vacio') ?>"></label>
            <label>Código marca <input type="text" name="cod_marca" maxlength="10" value="<?= $val('cod_marca') ?>"></label>
            <label>Marca <input type="text" name="marca" maxlength="30" placeholder="(la trae el RNDC)" value="<?= $val('marca') ?>"></label>
            <label>Modelo (año) <input type="number" name="ano_fabricacion" value="<?= $val('ano_fabricacion') ?>"></label>
            <label>Número de ejes <input type="number" name="num_ejes" value="<?= $val('num_ejes') ?>"></label>
            <label>Código carrocería <input type="text" name="cod_carroceria" maxlength="3" value="<?= $val('cod_carroceria') ?>"></label>
            <label>Capacidad <input type="number" name="capacidad" value="<?= $val('capacidad') ?>"></label>
            <label>Código combustible <input type="text" name="cod_combustible" maxlength="2" value="<?= $val('cod_combustible') ?>"></label>
            <label>Número de chasis <input type="text" name="num_chasis" maxlength="50" value="<?= $val('num_chasis') ?>"></label>
        </div>
    </fieldset>

    <fieldset>
        <legend>SOAT (opcional)</legend>
        <div class="grid">
            <label>Número póliza SOAT <input type="text" name="num_soat" maxlength="15" value="<?= $val('num_soat') ?>"></label>
            <label>Vencimiento SOAT <input type="date" name="venc_soat" value="<?= $val('venc_soat') ?>"></label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Tenedor (obligatorio — es un tercero)</legend>
        <label class="ancho-total">Buscar tercero (tenedor) *
            <div class="autocompletar" data-ac="terceros">
                <input type="text" class="ac-texto" autocomplete="off" placeholder="Nombre o identificación…" value="<?= e($tenedorTxt) ?>" <?= $editar ? '' : 'required' ?>>
                <ul class="ac-lista"></ul>
                <input type="hidden" name="tenedor_tipo_id" data-ac-field="tipo_id" value="<?= $val('tenedor_tipo_id') ?>">
                <input type="hidden" name="tenedor_num_id" data-ac-field="num_id" value="<?= $val('tenedor_num_id') ?>">
            </div>
        </label>
        <p class="ayuda">¿No existe? <a href="<?= e(ruta('tercero.nuevo')) ?>" target="_blank">Crear tercero</a>.</p>
    </fieldset>

    <fieldset>
        <legend>Propietario (opcional — lo hereda el RNDC)</legend>
        <label class="ancho-total">Buscar tercero (propietario)
            <div class="autocompletar" data-ac="terceros">
                <input type="text" class="ac-texto" autocomplete="off" placeholder="Nombre o identificación…" value="<?= e($propTxt) ?>">
                <ul class="ac-lista"></ul>
                <input type="hidden" name="propietario_tipo_id" data-ac-field="tipo_id" value="<?= $val('propietario_tipo_id') ?>">
                <input type="hidden" name="propietario_num_id" data-ac-field="num_id" value="<?= $val('propietario_num_id') ?>">
            </div>
        </label>
    </fieldset>

    <div class="acciones">
        <button type="submit" class="btn btn--primario"><?= $editar ? 'Actualizar' : 'Guardar' ?> vehículo</button>
        <a href="<?= e(ruta('vehiculos')) ?>" class="btn">Cancelar</a>
    </div>
</form>
