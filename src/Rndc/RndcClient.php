<?php
/**
 * Light TMS - Cliente del web service del RNDC.
 *
 * ESQUELETO (Fase 1). La implementación real del envío SOAP/XML se
 * desarrolla en la Fase 2:
 *   - armar el sobre <root><acceso>...</acceso><solicitud>N</solicitud>...
 *   - postear el XML (cURL) y parsear <ingresoid> / <errores>.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

final class RndcClient
{
    public function __construct(
        private readonly string $url,
        private readonly string $username,
        private readonly string $password,
        private readonly string $empresa,
    ) {
    }

    public static function desdeConfig(): self
    {
        $cfg = config()['rndc'];
        return new self(
            (string) $cfg['url'],
            (string) $cfg['username'],
            (string) $cfg['password'],
            (string) $cfg['empresa'],
        );
    }

    /**
     * Construye el bloque <acceso> de autenticación.
     */
    public function bloqueAcceso(): string
    {
        return sprintf(
            '<acceso><username>%s</username><password>%s</password></acceso>',
            htmlspecialchars($this->username, ENT_XML1, 'UTF-8'),
            htmlspecialchars($this->password, ENT_XML1, 'UTF-8'),
        );
    }

    /**
     * Envía un payload XML al RNDC.
     *
     * @return array{ok:bool, ingresoid:?string, respuesta:string, error:?string}
     * @throws RuntimeException mientras no esté implementado (Fase 2).
     */
    public function enviar(string $payloadXml): array
    {
        // TODO (Fase 2): implementar POST cURL al web service y parseo de respuesta.
        throw new RuntimeException('RndcClient::enviar() pendiente de implementar (Fase 2).');
    }
}
