# Integración con el RNDC

Documentación de la capa de integración con el web service del **RNDC**
(Registro Nacional de Despachos de Carga), Ministerio de Transporte de Colombia.

## Cómo funciona

El RNDC expone una operación **SOAP 1.1**: `AtenderMensajeRNDC`, que recibe un
parámetro string `<Request>` con un **XML interno escapado**:

```xml
<root>
  <acceso><username>..</username><password>..</password></acceso>
  <solicitud><tipo>1</tipo><procesoid>11</procesoid></solicitud>
  <variables>
    <NOMVARIABLE>valor</NOMVARIABLE>
    ...
  </variables>
</root>
```

- `tipo` = `1` para **ingresar/expedir** información.
- `procesoid` = número de proceso (ver abajo).
- La respuesta trae `<ingresoid>` (éxito) o `<ErrorMSG>` / `<error>` (rechazo).

**Headers:** `Content-Type: text/xml; charset=ISO-8859-1` y
`SOAPAction: urn:BPMServicesIntf-IBPMServices#AtenderMensajeRNDC`.
Ruta del servicio: `/soap/IBPMServices`.

> **Encoding:** el RNDC *declara* ISO-8859-1 pero en la práctica **responde en UTF-8**.
> El cliente lo detecta y solo convierte si los bytes no son UTF-8 válido
> (evita la doble codificación de acentos).

## Servidores (balanceo de carga oficial)

| Servidor | Host:puerto | Procesos | Estado verificado (2026-06-18) |
|---|---|---|---|
| Pruebas | `rndc.mintransporte.gov.co:8080` | todos (ambiente prueba) | ❌ **no responde** |
| Expedir | `rndcws2.mintransporte.gov.co:8080` | **3 (Remesa), 4 (Manifiesto)** | ✅ responde |
| Consultas | `plc.mintransporte.gov.co:8080` | 26, 27, 48, 55 | ✅ responde |
| Otros | `rndcws.mintransporte.gov.co:8080` | 1, 2, 5..12, etc. | ✅ responde |

> El servidor de **pruebas (`rndc`)** estaba caído en la verificación. Los de
> producción responden. Para acceder con credenciales reales puede requerirse
> **autorizar la IP** del servidor ante el Ministerio.

El cliente resuelve el endpoint automáticamente con `RNDC_AMBIENTE`
(`pruebas` | `produccion`) y enruta por `procesoid`. Se puede forzar con
`RNDC_HOST_OVERRIDE`.

## Procesos clave para Light TMS

| procesoid | Nombre | Campos | Uso |
|---|---|---|---|
| 11 | Tercero | 20 | Registrar remitente/destinatario/propietario/conductor |
| 12 | Vehículo | 30 | Registrar el vehículo |
| **3** | **Remesa Terrestre de Carga** | 60 | Documento de la carga |
| **4** | **Manifiesto de Carga** | 46 | Documento del viaje |

Orden de envío: **Terceros → Vehículo → Remesa → Manifiesto**.

El diccionario completo de variables oficiales está en:
- `docs/diccionario_rndc.csv` (fuente, UTF-8)
- `src/Rndc/Diccionario.php` (generado, usado por el código)

Para regenerar el PHP desde el CSV:

```bash
iconv -f ISO-8859-1 -t UTF-8 "Maestro_Diccionario de Datos_RNDC.csv" \
  | awk -f tools/_gen_diccionario.awk > src/Rndc/Diccionario.php
```

## Uso del cliente (PHP)

```php
require_once __DIR__ . '/src/Rndc/RndcClient.php';

$rndc = RndcClient::desdeConfig();          // usa el .env
$resp = $rndc->ingresar(11, [               // proceso 11 = Tercero
    'NUMNITEMPRESATRANSPORTE' => '900000000',
    'CODTIPOIDTERCERO'        => 'N',
    'NUMIDTERCERO'            => '12345678',
    'NOMIDTERCERO'            => 'ACME SAS',
    // ...
]);

if ($resp->ok) {
    echo "ingresoid: {$resp->ingresoId}";
} else {
    echo "Error: {$resp->error}";
}
```

Probar desde la terminal:

```bash
php tools/probar_rndc.php                  # muestra el XML, sin enviar
php tools/probar_rndc.php --enviar         # envía (usa el .env)
php tools/probar_rndc.php --enviar --host="http://rndcws2.mintransporte.gov.co:8080"
```
