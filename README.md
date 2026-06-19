# Light TMS

Mini TMS (Transport Management System) para Colombia con capa **store-and-forward**
hacia el **RNDC** (Registro Nacional de Despachos de Carga). Cuando el web service del
RNDC está caído, la información se guarda localmente y se reenvía cuando vuelve a estar
disponible.

## Concepto central

La **Solicitud de Servicio** se captura **una sola vez** y siembra automáticamente el
**Manifiesto** y la **Remesa**. El usuario no crea esos documentos por separado.

## Stack

Pensado para **hosting web/cloud de Hostinger** (LAMP administrado):

- **PHP** (sin Composer; se sube por FTP/File Manager)
- **MariaDB** (compatible MySQL), administrada con **phpMyAdmin**
- Cliente RNDC en PHP (SOAP/XML)
- **Cron job** de Hostinger para el worker de reintento

## Estructura

```
light-tms/
├── public/                  <- document root del dominio
│   ├── index.php            tablero de inicio (verifica BD)
│   └── assets/css/styles.css
├── src/
│   ├── config.php           carga .env + config()
│   ├── db.php               conexión PDO a MariaDB
│   ├── helpers.php          utilidades
│   └── Rndc/RndcClient.php  cliente RNDC (Fase 2)
├── cron/
│   └── retry_worker.php     worker store-and-forward (Fase 4)
├── sql/
│   └── schema.sql           tablas (importar en phpMyAdmin)
├── .env.example
└── README.md
```

## Modelo de datos

- `solicitud_servicio` — captura única (siembra manifiesto + remesa)
- `manifiesto` — documento RNDC (ManifiestoCarga)
- `remesa` — documento RNDC (RemesaTerrestreCarga)
- `cola_envios` — bandeja store-and-forward (estado: pendiente / enviando / enviado / error)

Los campos llevan en comentarios SQL su variable oficial del RNDC entre `[corchetes]`.

## Puesta en marcha (local)

1. Copia `.env.example` a `.env` y completa los datos de la BD.
2. Crea la base de datos e importa, en orden, los SQL de `sql/`:
   `schema.sql` (documentos), `municipios.sql` (catálogo DIVIPOLA),
   `maestros.sql` (Tercero y Vehículo), `catalogos.sql`
   (empaque, carrocería, producto, errores RNDC), `productos_subpartidas.sql`
   (mercancía general), `migracion_v2.sql`, `migracion_v3.sql`
   (campos de despacho, maestro_empresa, retenciones), `migracion_v4.sql`
   (vehículo solo requeridos + remolque) y `catalogo_configuracion.sql`
   (configuración de unidad de carga).
3. Sirve la carpeta `public/`:
   ```bash
   php -S localhost:8000 -t public
   ```
4. Abre <http://localhost:8000> — el tablero debe mostrar "Base de datos conectada".

## Despliegue en Hostinger

1. En hPanel crea una **base de datos MySQL** y un usuario; anota host, nombre, usuario y clave.
2. Importa `sql/schema.sql` desde **phpMyAdmin**.
3. Sube los archivos por **File Manager / FTP** (p. ej. a `domains/TU_DOMINIO/light-tms`).
4. Apunta el **document root** del dominio/subdominio a la carpeta `public/`
   (hPanel > Avanzado > Document root). Así `src/`, `cron/` y `.env` quedan fuera de la web.
5. Crea el archivo `.env` en el servidor con las credenciales reales.
6. Configura un **Cron Job** (Fase 4):
   ```
   */15 * * * * /usr/bin/php /home/USUARIO/domains/TU_DOMINIO/light-tms/cron/retry_worker.php
   ```

## Estado

- [x] **Fase 1** — Esqueleto + esquema de BD
- [x] **Fase 2** — Cliente RNDC (SOAP/XML + `<acceso>`) — ver [docs/RNDC.md](docs/RNDC.md)
- [x] **Fase 3** — Flujo Solicitud de Servicio (captura única → siembra Manifiesto + Remesa)
- [ ] **Fase 4** — Worker de reintento (cron / envío a RNDC)

> El cliente RNDC (`src/Rndc/RndcClient.php`) está verificado de extremo a extremo
> contra el servidor real del RNDC. Solo falta cargar credenciales válidas en `.env`.
