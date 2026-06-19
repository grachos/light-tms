<?php
/**
 * Light TMS - Repositorio de Solicitud de Servicio.
 *
 * La Solicitud se captura UNA vez y al guardarla SIEMBRA automáticamente
 * un Manifiesto y una Remesa (estado_rndc = 'pendiente'), heredando los
 * datos base. El usuario no crea esos documentos por separado.
 */

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

final class SolicitudRepo
{
    /** Campos aceptados desde el formulario (lista blanca). */
    private const CAMPOS = [
        'consecutivo', 'fecha_solicitud',
        'operacion_transporte', 'municipio_origen', 'municipio_destino',
        'empresa_tipo_id', 'empresa_num_id',
        'placa_vehiculo', 'conductor_tipo_id', 'conductor_num_id',
        'remitente_tipo_id', 'remitente_num_id',
        'destinatario_tipo_id', 'destinatario_num_id',
        'naturaleza_carga', 'descripcion_producto', 'cantidad_cargada', 'unidad_medida',
        'valor_flete', 'valor_anticipo',
        'observaciones',
    ];

    /**
     * Inserta la solicitud y siembra manifiesto + remesa en una transacción.
     *
     * @param array<string,mixed> $datos
     * @return int id de la solicitud creada
     */
    public function crear(array $datos): int
    {
        $fila = [];
        foreach (self::CAMPOS as $c) {
            $valor = $datos[$c] ?? null;
            $fila[$c] = ($valor === '' ? null : $valor);
        }
        if (empty($fila['fecha_solicitud'])) {
            $fila['fecha_solicitud'] = date('Y-m-d');
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $cols = implode(', ', array_keys($fila));
            $ph   = implode(', ', array_map(static fn ($c) => ":$c", array_keys($fila)));
            $stmt = $pdo->prepare("INSERT INTO solicitud_servicio ($cols) VALUES ($ph)");
            $stmt->execute($fila);
            $id = (int) $pdo->lastInsertId();

            $this->sembrarRemesa($pdo, $id, $fila);
            $this->sembrarManifiesto($pdo, $id, $fila);

            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @param array<string,mixed> $s */
    private function sembrarRemesa(PDO $pdo, int $solicitudId, array $s): void
    {
        $remesa = [
            'solicitud_id'         => $solicitudId,
            'num_remesa'           => $s['consecutivo'] ?? null,
            'operacion_transporte' => $s['operacion_transporte'] ?? null,
            'naturaleza_carga'     => $s['naturaleza_carga'] ?? null,
            'descripcion_producto' => $s['descripcion_producto'] ?? null,
            'cantidad_cargada'     => $s['cantidad_cargada'] ?? null,
            'unidad_medida'        => $s['unidad_medida'] ?? null,
            'remitente_tipo_id'    => $s['remitente_tipo_id'] ?? null,
            'remitente_num_id'     => $s['remitente_num_id'] ?? null,
            'destinatario_tipo_id' => $s['destinatario_tipo_id'] ?? null,
            'destinatario_num_id'  => $s['destinatario_num_id'] ?? null,
            'municipio_cargue'     => $s['municipio_origen'] ?? null,
            'municipio_descargue'  => $s['municipio_destino'] ?? null,
        ];
        $this->insertar($pdo, 'remesa', $remesa);
    }

    /** @param array<string,mixed> $s */
    private function sembrarManifiesto(PDO $pdo, int $solicitudId, array $s): void
    {
        $manifiesto = [
            'solicitud_id'         => $solicitudId,
            'num_manifiesto'       => $s['consecutivo'] ?? null,
            'fecha_expedicion'     => $s['fecha_solicitud'] ?? null,
            'operacion_transporte' => $s['operacion_transporte'] ?? null,
            'municipio_origen'     => $s['municipio_origen'] ?? null,
            'municipio_destino'    => $s['municipio_destino'] ?? null,
            'titular_tipo_id'      => $s['empresa_tipo_id'] ?? null,
            'titular_num_id'       => $s['empresa_num_id'] ?? null,
            'placa_vehiculo'       => $s['placa_vehiculo'] ?? null,
            'conductor_tipo_id'    => $s['conductor_tipo_id'] ?? null,
            'conductor_num_id'     => $s['conductor_num_id'] ?? null,
            'valor_flete_pactado'  => $s['valor_flete'] ?? null,
            'valor_anticipo'       => $s['valor_anticipo'] ?? null,
        ];
        $this->insertar($pdo, 'manifiesto', $manifiesto);
    }

    /** @param array<string,mixed> $fila */
    private function insertar(PDO $pdo, string $tabla, array $fila): void
    {
        $cols = implode(', ', array_keys($fila));
        $ph   = implode(', ', array_map(static fn ($c) => ":$c", array_keys($fila)));
        $pdo->prepare("INSERT INTO `$tabla` ($cols) VALUES ($ph)")->execute($fila);
    }

    /** @return list<array<string,mixed>> */
    public function listar(int $limite = 100): array
    {
        $stmt = db()->query(
            'SELECT id, consecutivo, fecha_solicitud, municipio_origen, municipio_destino,
                    placa_vehiculo, estado, created_at
             FROM solicitud_servicio
             ORDER BY id DESC
             LIMIT ' . (int) $limite
        );
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function obtener(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM solicitud_servicio WHERE id = ?');
        $stmt->execute([$id]);
        $solicitud = $stmt->fetch();
        if (!$solicitud) {
            return null;
        }

        $m = db()->prepare('SELECT * FROM manifiesto WHERE solicitud_id = ?');
        $m->execute([$id]);
        $r = db()->prepare('SELECT * FROM remesa WHERE solicitud_id = ?');
        $r->execute([$id]);

        return [
            'solicitud'  => $solicitud,
            'manifiesto' => $m->fetch() ?: null,
            'remesa'     => $r->fetch() ?: null,
        ];
    }
}
