<?php
/**
 * Vista: formulario de nuevo Vehículo (proceso 12).
 * Marca/modelo/propietario son opcionales: el RNDC los hereda del RUNT por placa.
 */
declare(strict_types=1);
?>
<h1>Nuevo Vehículo</h1>
<p class="ayuda">Lo mínimo es placa, configuración, peso vacío y tenedor.
   Marca, modelo y propietario son opcionales: si los dejas vacíos, el RNDC los
   completa desde el RUNT por la placa.</p>

<?php flash(); ?>

<form method="post" action="<?= e(ruta('vehiculo.crear')) ?>" class="form">

    <fieldset>
        <legend>Características generales</legend>
        <div class="grid">
            <label>Placa *
                <input type="text" name="placa" maxlength="6" required placeholder="ABC123" style="text-transform:uppercase">
            </label>
            <label>Configuración *
                <input type="text" name="cod_configuracion" maxlength="2" required placeholder="p. ej. 2, 3, 55">
            </label>
            <label>Peso vacío (kg) *
                <input type="number" name="peso_vacio" required placeholder="8000">
            </label>
            <label>Código marca
                <input type="text" name="cod_marca" maxlength="10" placeholder="(opcional)">
            </label>
            <label>Marca
                <input type="text" name="marca" maxlength="30" placeholder="(la trae el RNDC)">
            </label>
            <label>Modelo (año)
                <input type="number" name="ano_fabricacion" placeholder="(opcional)">
            </label>
            <label>Número de ejes
                <input type="number" name="num_ejes" placeholder="(opcional)">
            </label>
            <label>Código carrocería
                <input type="text" name="cod_carroceria" maxlength="3" placeholder="(opcional)">
            </label>
            <label>Capacidad
                <input type="number" name="capacidad" placeholder="(opcional)">
            </label>
            <label>Código combustible
                <input type="text" name="cod_combustible" maxlength="2" placeholder="(opcional)">
            </label>
            <label>Número de chasis
                <input type="text" name="num_chasis" maxlength="50" placeholder="(opcional)">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>SOAT (opcional)</legend>
        <div class="grid">
            <label>Número póliza SOAT
                <input type="text" name="num_soat" maxlength="15">
            </label>
            <label>Vencimiento SOAT
                <input type="date" name="venc_soat">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Tenedor (obligatorio — es un tercero)</legend>
        <label class="ancho-total">Buscar tercero (tenedor) *
            <div class="autocompletar" data-ac="terceros">
                <input type="text" class="ac-texto" autocomplete="off" placeholder="Nombre o identificación…" required>
                <ul class="ac-lista"></ul>
                <input type="hidden" name="tenedor_tipo_id" data-ac-field="tipo_id">
                <input type="hidden" name="tenedor_num_id" data-ac-field="num_id">
            </div>
        </label>
        <p class="ayuda">¿No existe? <a href="<?= e(ruta('tercero.nuevo')) ?>" target="_blank">Crear tercero</a>.</p>
    </fieldset>

    <fieldset>
        <legend>Propietario (opcional — es un tercero; si se omite, lo hereda el RNDC)</legend>
        <label class="ancho-total">Buscar tercero (propietario)
            <div class="autocompletar" data-ac="terceros">
                <input type="text" class="ac-texto" autocomplete="off" placeholder="Nombre o identificación…">
                <ul class="ac-lista"></ul>
                <input type="hidden" name="propietario_tipo_id" data-ac-field="tipo_id">
                <input type="hidden" name="propietario_num_id" data-ac-field="num_id">
            </div>
        </label>
    </fieldset>

    <div class="acciones">
        <button type="submit" class="btn btn--primario">Guardar vehículo</button>
        <a href="<?= e(ruta('vehiculos')) ?>" class="btn">Cancelar</a>
    </div>
</form>
