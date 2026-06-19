<?php
/**
 * Light TMS - Cliente del web service del RNDC (SOAP/XML).
 *
 * Portado del Google Apps Script funcional. El RNDC expone la operación
 * SOAP 1.1 "AtenderMensajeRNDC", que recibe un parámetro string <Request>
 * con un XML interno escapado:
 *
 *   <root>
 *     <acceso><username>..</username><password>..</password></acceso>
 *     <solicitud><tipo>..</tipo><procesoid>..</procesoid></solicitud>
 *     <variables>...</variables>
 *   </root>
 *
 * Respuesta: el <return> trae otro XML con <ingresoid> (éxito) o
 * <ErrorMSG>/<error> (rechazo).
 *
 * Balanceo de carga (tabla oficial del Ministerio):
 *   - Pruebas:        rndc.mintransporte.gov.co:8080  (todos los procesos)
 *   - Expedir 3 y 4:  rndcws2.mintransporte.gov.co:8080 (Remesa / Manifiesto)
 *   - Consultas:      plc.mintransporte.gov.co:8080  (26, 27, 48, 55)
 *   - Otros procesos: rndcws.mintransporte.gov.co:8080
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/RndcRespuesta.php';

final class RndcClient
{
    private const PATH        = '/soap/IBPMServices';
    private const SOAP_ACTION = 'urn:BPMServicesIntf-IBPMServices#AtenderMensajeRNDC';
    private const NS_URN      = 'urn:BPMServicesIntf-IBPMServices';

    /** Tipo de solicitud para ingresar/expedir información. */
    public const TIPO_INGRESAR = '1';

    /** Procesos enrutados a servidores específicos en producción. */
    private const PROCESOS_EXPEDIR   = [3, 4];               // Remesa, Manifiesto
    private const PROCESOS_CONSULTAS = [26, 27, 48, 55];     // Consultas

    private const HOSTS = [
        'pruebas'   => 'http://rndc.mintransporte.gov.co:8080',
        'expedir'   => 'http://rndcws2.mintransporte.gov.co:8080',
        'consultas' => 'http://plc.mintransporte.gov.co:8080',
        'otros'     => 'http://rndcws.mintransporte.gov.co:8080',
    ];

    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $ambiente = 'pruebas',
        private readonly string $hostOverride = '',
        private readonly int $timeout = 30,
    ) {
    }

    public static function desdeConfig(): self
    {
        $cfg = config()['rndc'];
        return new self(
            (string) $cfg['username'],
            (string) $cfg['password'],
            (string) ($cfg['ambiente'] ?? 'pruebas'),
            (string) ($cfg['host_override'] ?? ''),
            (int) ($cfg['timeout'] ?? 30),
        );
    }

    /**
     * Resuelve la URL completa del web service según el proceso y el ambiente.
     */
    public function endpointPara(int $procesoid): string
    {
        if ($this->hostOverride !== '') {
            return rtrim($this->hostOverride, '/') . self::PATH;
        }
        if ($this->ambiente !== 'produccion') {
            return self::HOSTS['pruebas'] . self::PATH;
        }
        if (in_array($procesoid, self::PROCESOS_EXPEDIR, true)) {
            return self::HOSTS['expedir'] . self::PATH;
        }
        if (in_array($procesoid, self::PROCESOS_CONSULTAS, true)) {
            return self::HOSTS['consultas'] . self::PATH;
        }
        return self::HOSTS['otros'] . self::PATH;
    }

    /**
     * Atajo para ingresar/expedir información de un proceso (tipo = 1).
     *
     * @param array<string,scalar|null> $variables
     */
    public function ingresar(int $procesoid, array $variables, ?array $documento = null): RndcRespuesta
    {
        return $this->enviar(self::TIPO_INGRESAR, $procesoid, $variables, $documento);
    }

    /**
     * Envía una solicitud al RNDC y devuelve la respuesta parseada.
     *
     * @param array<string,scalar|null> $variables
     * @param array<string,scalar|null>|null $documento
     */
    public function enviar(string $tipo, int $procesoid, array $variables, ?array $documento = null): RndcRespuesta
    {
        $xmlInterno = $this->construirXmlInterno($tipo, $procesoid, $variables, $documento);
        $sobre      = $this->construirSobreSoap($xmlInterno);
        $url        = $this->endpointPara($procesoid);

        // El RNDC trabaja en ISO-8859-1.
        $cuerpo = mb_convert_encoding($sobre, 'ISO-8859-1', 'UTF-8');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $cuerpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=ISO-8859-1',
                'SOAPAction: ' . self::SOAP_ACTION,
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        $respuesta = curl_exec($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            return RndcRespuesta::fallo("Error de conexión: $curlError", 0, '', $xmlInterno);
        }

        // El RNDC declara ISO-8859-1 pero normalmente responde en UTF-8.
        // Convertimos solo si los bytes NO son UTF-8 válido (evita doble codificación).
        $respuesta = (string) $respuesta;
        $respuestaUtf8 = mb_check_encoding($respuesta, 'UTF-8')
            ? $respuesta
            : mb_convert_encoding($respuesta, 'UTF-8', 'ISO-8859-1');

        if ($httpCode < 200 || $httpCode >= 300) {
            return RndcRespuesta::fallo("HTTP $httpCode", $httpCode, $respuestaUtf8, $xmlInterno);
        }

        return $this->parsearRespuesta($respuestaUtf8, $httpCode, $xmlInterno);
    }

    /**
     * Construye el XML interno (parámetro <Request>) en UTF-8.
     *
     * @param array<string,scalar|null> $variables
     * @param array<string,scalar|null>|null $documento
     */
    public function construirXmlInterno(string $tipo, int $procesoid, array $variables, ?array $documento = null): string
    {
        $xml  = "<?xml version='1.0' encoding='ISO-8859-1'?>\n<root>\n";
        $xml .= "  <acceso>\n";
        $xml .= '    <username>' . self::escaparXml($this->username) . "</username>\n";
        $xml .= '    <password>' . self::escaparXml($this->password) . "</password>\n";
        $xml .= "  </acceso>\n";
        $xml .= "  <solicitud>\n";
        $xml .= '    <tipo>' . self::escaparXml($tipo) . "</tipo>\n";
        $xml .= '    <procesoid>' . $procesoid . "</procesoid>\n";
        $xml .= "  </solicitud>\n";
        $xml .= "  <variables>\n";
        foreach ($variables as $clave => $valor) {
            if ($valor === null || $valor === '') {
                continue; // No enviar variables vacías.
            }
            $xml .= '    <' . $clave . '>' . self::escaparXml((string) $valor) . '</' . $clave . ">\n";
        }
        $xml .= "  </variables>\n";
        if ($documento !== null && $documento !== []) {
            $xml .= "  <documento>\n";
            foreach ($documento as $clave => $valor) {
                $xml .= '    <' . $clave . '>' . self::escaparXml((string) $valor) . '</' . $clave . ">\n";
            }
            $xml .= "  </documento>\n";
        }
        $xml .= '</root>';
        return $xml;
    }

    /**
     * Envuelve el XML interno en el sobre SOAP 1.1 de AtenderMensajeRNDC.
     */
    public function construirSobreSoap(string $xmlInterno): string
    {
        $req = self::escaparXml($xmlInterno);
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            . 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
            . 'xmlns:urn="' . self::NS_URN . "\">\n"
            . "  <soapenv:Header/>\n"
            . "  <soapenv:Body>\n"
            . '    <urn:AtenderMensajeRNDC soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">' . "\n"
            . '      <Request xsi:type="xsd:string">' . $req . "</Request>\n"
            . "    </urn:AtenderMensajeRNDC>\n"
            . "  </soapenv:Body>\n"
            . '</soapenv:Envelope>';
    }

    /**
     * Extrae el <return> del sobre SOAP y parsea el XML interno de resultado.
     */
    private function parsearRespuesta(string $respuesta, int $httpCode, string $xmlEnviado): RndcRespuesta
    {
        $return = $this->extraerNodo($respuesta, 'return');
        if ($return === null) {
            // Puede ser un SOAP Fault.
            $fault = $this->extraerNodo($respuesta, 'faultstring');
            $msg = $fault !== null ? "SOAP Fault: $fault" : 'Estructura de respuesta inesperada (sin <return>).';
            return RndcRespuesta::fallo($msg, $httpCode, $respuesta, $xmlEnviado);
        }

        // El contenido de <return> es a su vez un XML (ya des-escapado por el parser).
        $ingreso = $this->extraerNodo($return, 'ingresoid');
        if ($ingreso !== null && $ingreso !== '') {
            return RndcRespuesta::exito($ingreso, $httpCode, $return, $xmlEnviado);
        }

        $errorMsg = $this->extraerNodo($return, 'ErrorMSG')
            ?? $this->extraerNodo($return, 'error');
        if ($errorMsg !== null) {
            return RndcRespuesta::fallo($errorMsg, $httpCode, $return, $xmlEnviado);
        }

        return RndcRespuesta::fallo('Respuesta sin <ingresoid> ni error.', $httpCode, $return, $xmlEnviado);
    }

    /**
     * Devuelve el textContent del primer elemento con el nombre dado (ignora namespaces).
     */
    private function extraerNodo(string $xml, string $nombre): ?string
    {
        // Quita la declaracion XML inicial: ya trabajamos en UTF-8, pero el RNDC
        // declara ISO-8859-1 y eso haria que DOMDocument re-decodifique mal.
        $xml = preg_replace('/<\?xml[^>]*\?>/i', '', $xml) ?? $xml;
        $xml = trim($xml);
        if ($xml === '') {
            return null;
        }
        $dom = new DOMDocument();
        $previo = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previo);
        if (!$ok) {
            return null;
        }
        $nodos = $dom->getElementsByTagName($nombre);
        if ($nodos->length === 0) {
            return null;
        }
        return trim($nodos->item(0)->textContent);
    }

    /**
     * Escapa caracteres especiales de XML (igual que el script original).
     */
    public static function escaparXml(string $valor): string
    {
        return strtr($valor, [
            '&'  => '&amp;',
            '<'  => '&lt;',
            '>'  => '&gt;',
            "'"  => '&apos;',
            '"'  => '&quot;',
        ]);
    }
}
