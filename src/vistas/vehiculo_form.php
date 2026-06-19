<?php
/**
 * Vista: formulario de nuevo Vehículo (proceso 12).
 * Marca/modelo/propietario son opcionales: el RNDC los hereda del RUNT por placa.
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
        <legend>Tenedor (obligatorio)</legend>
        <div class="grid">
            <label>Tipo id. tenedor *
                <select name="tenedor_tipo_id" required>
                    <?php foreach ($tiposId as $v => $t): ?><option value="<?= e($v) ?>"><?= e($t) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Número id. tenedor *
                <input type="text" name="tenedor_num_id" maxlength="15" required>
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Propietario (opcional — lo hereda el RNDC)</legend>
        <div class="grid">
            <label>Tipo id. propietario
                <select name="propietario_tipo_id">
                    <option value="">—</option>
                    <?php foreach ($tiposId as $v => $t): ?><option value="<?= e($v) ?>"><?= e($t) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Número id. propietario
                <input type="text" name="propietario_num_id" maxlength="15">
            </label>
        </div>
    </fieldset>

    <div class="acciones">
        <button type="submit" class="btn btn--primario">Guardar vehículo</button>
        <a href="<?= e(ruta('vehiculos')) ?>" class="btn">Cancelar</a>
    </div>
</form>
