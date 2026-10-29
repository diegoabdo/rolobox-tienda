# Plan de despliegue de Twist & Rolls

Cómo publicar la tienda en internet con su propio dominio: qué contratar, cuánto cuesta, qué registros DNS crear y en qué orden hacer cada paso.

> **Los precios son de octubre de 2026,** tomados de las páginas de cada proveedor y de comparativas recientes, a unos **Q7.7 por dólar**. Confírmalos antes de pagar: cambian seguido.

## 0. Para desplegar mañana: la hoja corta

**Hoy (30 minutos)**

1. **Sube el rediseño a `master`.** Todo el trabajo está en la rama `dabdo/ui_redesign`, sin commit. El servidor despliega desde `master` en GitHub. Hacer commit, abrir el PR y unirlo.
2. **Compra el dominio** `twistrolls.com` en GoDaddy (≈ €11 al año). Titular: Unique; correo de contacto: lorenaestrada@hotmail.com; sin extras (ni correo, ni hosting, ni SSL, ni «protección»); renovación automática activada.
3. **Crea las cuentas:** Cloudflare (gratis), DigitalOcean, Laravel Forge y GitHub (si el repositorio todavía no es privado, ponlo privado). Ten una tarjeta con compras internacionales.
4. **Ten a la mano:** el número de WhatsApp del negocio (`502XXXXXXXX`), las llaves de prueba de Recurrente (`sk_test_…`) y los precios reales.

**Mañana (≈ 3 horas, con la cuenta de Recurrente en prueba)**

| Hora | Qué | Paso |
| --- | --- | --- |
| 0:00 | Cloudflare: agregar el dominio y cambiar los nameservers en GoDaddy | 2 |
| 0:15 | DigitalOcean + Forge: crear el servidor (2 GB, Nueva York, PHP 8.4, MySQL 8, Node 22) | 3 |
| 0:45 | DNS: registro A a la IP del servidor | 4 |
| 1:00 | Forge: sitio, `.env`, script de despliegue y primer despliegue | 5 |
| 1:30 | SSL, SSR y programador de tareas | 6 y 7 |
| 1:45 | Datos iniciales, tu usuario del panel, precios reales | 8 |
| 2:15 | Recurrente en modo prueba y compras de prueba | 10 |
| 2:45 | Lista de revisión | 11 |

**Lo que no depende de ti y puede frenar el lanzamiento:** la cuenta comercial de Recurrente (verificación de la empresa, puede tardar días) y la revisión legal de términos y privacidad. Puedes publicar la tienda en modo prueba mañana y activar los cobros reales cuando Recurrente te apruebe.

**Qué se carga al instalar:** el seeder crea un catálogo **de ejemplo** (rolls, croissants, focaccias, trenzas y sus precios, zonas de Xela y la promoción BIENVENIDA). **Cámbialo desde el panel antes de anunciar.** La «Caja mixta de seis» queda desactivada.
## 1. La recomendación en corto

| Pieza | Qué usar | Por qué |
| --- | --- | --- |
| Dominio | `twistrolls.com`, comprado en GoDaddy (≈ €11 al año) | El nombre de la marca; un `.com` se compra en minutos y no pide documentos |
| DNS | Cloudflare (gratis) | Rápido, gratis y con reenvío de correo incluido |
| Servidor | DigitalOcean, Droplet de 2 GB, región Nueva York | Suficiente para la tienda, el panel y el SSR, con disco persistente para las fotos |
| Administración del servidor | Laravel Forge | Instala PHP, la base de datos, el SSL y el despliegue sin tocar la terminal |
| Base de datos | MySQL 8 en el mismo servidor | Forge la instala y la configura |
| SSL | Let's Encrypt, desde Forge (gratis) | Se renueva solo |
| Pagos | Recurrente, cuenta comercial a nombre de Unique | Ya está integrado; la cuenta comercial cobra menos comisión |
| Correo | Cloudflare Email Routing → lorenaestrada@hotmail.com | Recibes en tu Hotmail lo que llegue a `pedidos@twistrolls.com` |

**Primer mes: ≈ Q300. Cada mes después: ≈ Q203 (US$26.40).** El dominio se paga una vez al año (≈ €11, unos Q98), no cada mes. Calculado a Q7.7 por dólar y Q8.9 por euro.

## 2. Presupuesto

### Opción A · Recomendada: DigitalOcean + Forge

| Concepto | Mensual | Anual |
| --- | --- | --- |
| Droplet 2 GB / 1 vCPU / 50 GB SSD | US$12.00 | US$144.00 |
| Respaldos semanales del Droplet (20% del Droplet) | US$2.40 | US$28.80 |
| Laravel Forge, plan Hobby | US$12.00 | US$144.00 (≈17% menos pagando el año) |
| Dominio `twistrolls.com` (GoDaddy) | — | €11 (≈ Q98) |
| Cloudflare (DNS y correo), SSL, GitHub privado | Gratis | Gratis |
| **Total** | **≈ US$26.40 (≈ Q203) al mes** | **Primer mes ≈ Q300 con el dominio** |

### Opción B · Económica: DigitalOcean sin Forge

| Concepto | Mensual |
| --- | --- |
| Droplet 1 GB + 2 GB de swap | US$6.00 |
| Respaldos semanales | US$1.20 |
| Dominio | €11 al año (≈ Q98) |
| **Total** | **≈ US$7.20 (≈ Q55) al mes; primer mes ≈ Q153** |

Ahorras unos Q150 al mes, pero alguien tiene que instalar y mantener a mano Nginx, PHP-FPM, MySQL, Supervisor, Certbot y las actualizaciones de seguridad. Conviene solo si tú o alguien de confianza maneja servidores Linux.

### Opción C · Laravel Cloud (no la recomiendo por ahora)

El plan Starter cuesta US$5 al mes (incluye US$5 de uso), más la base de datos y el almacenamiento; para una tienda pequeña serían unos US$5–20 al mes, variable. **Requiere un cambio en el código antes:** las fotos que subes desde el panel se guardan en el disco del servidor, y en Laravel Cloud ese disco se borra en cada despliegue, así que habría que moverlas a S3 o R2. También habría que confirmar cómo corre ahí el SSR de Inertia.

### Comisiones de Recurrente (no son costo fijo)

Según la [página de precios de Recurrente](https://www.recurrente.com/precios):

- **Cuenta comercial** (empresa registrada en Guatemala): **4.5% + Q2** por pago con tarjeta.
- **Cuenta personal:** 5.25% + Q2 por pago con tarjeta.
- **Sin mensualidad** ni costo de implementación. Recurrente anuncia las transferencias sin comisión; confírmalo, junto con si se suma IVA a la comisión.

Ejemplo: una caja de Q118 pagada con tarjeta en cuenta comercial deja Q118 − (Q5.31 + Q2) = **≈ Q110.69**. Por eso la tienda ya cobra Q110 por transferencia.

## 3. Antes de empezar

Lo que más tarda es lo que no depende del código. Arráncalo primero:

1. **Cuenta comercial de Recurrente a nombre de Unique.** Pide documentos de la empresa y una verificación que puede tardar días. Mientras tanto puedes probar todo con las llaves de prueba.
2. **Revisión legal** de `resources/legal/terminos.html` y `privacidad.html` (siguen marcados como borrador).
3. **Precios reales.** Los del catálogo de ejemplo se cambian en el panel después de instalar.
4. **Número de WhatsApp** del negocio, con código de país (`502…`).
5. **Fotos propias** de los roles, si las tienes. Las actuales son recortes de la foto de la caja.
6. **Subir el rediseño a `master`.** Los cambios están en la rama `dabdo/ui_redesign`, sin commit. El servidor despliega desde `master` en GitHub (`diegoabdo/rolobox-tienda`).
7. **Misión y visión.** La página «Nosotros» tiene un texto de borrador (`resources/js/pages/Nosotros.tsx`); pon el texto real.
8. **Fotos de croissants, focaccias y trenzas.** Hasta que subas las tuyas desde el panel, se ven con dibujos.

## 4. Paso a paso

### Paso 1 · Comprar el dominio (hoy)

`twistrolls.com` es un `.com`: se registra en cualquier registrador internacional (GoDaddy, en tu caso) y **no en www.gt**, que solo maneja los dominios que terminan en `.gt`.

Al comprarlo en GoDaddy:

1. Revisa el precio de **renovación**, no solo el del primer año.
2. Titular: **Unique**, con un correo que controles.
3. La privacidad del titular (WHOIS) debe venir incluida.
4. **No compres extras:** el servidor es DigitalOcean, el SSL es gratis con Forge y el correo se reenvía gratis con Cloudflare.
5. Activa la renovación automática y revisa que la tarjeta no venza: si el dominio se vence, la tienda deja de funcionar.

El dominio sigue registrado y pagado en GoDaddy; solo cambias sus *nameservers* a los de Cloudflare (paso siguiente).
### Paso 2 · Pasar el DNS a Cloudflare (día 1–2)

1. Crea una cuenta gratis en Cloudflare y agrega el sitio `twistrolls.com` con el plan Free.
2. Cloudflare te da dos *nameservers* (algo como `ana.ns.cloudflare.com`). En el panel del registrador, reemplaza los nameservers por esos dos.
3. Espera a que Cloudflare marque el dominio como **Active**. Suele tardar minutos o unas horas, y hasta 48 horas.

### Paso 3 · Crear el servidor (día 2)

1. Crea cuentas en DigitalOcean y en Laravel Forge, y conecta DigitalOcean desde Forge (**Server providers**). Conecta también GitHub (**Source control**).
2. En Forge, **Create server** con:
   - Proveedor DigitalOcean, región **New York**: la más cercana a Guatemala entre las de DigitalOcean.
   - Tamaño **2 GB RAM / 1 vCPU**.
   - **PHP 8.4**, base de datos **MySQL 8**.
   - Forge te muestra la contraseña de la base de datos y la de root una sola vez. **Guárdalas** en un gestor de contraseñas.
3. En DigitalOcean, activa los **Backups** del Droplet (semanales).
4. Anota la **IP pública** del servidor: la necesitas para el DNS.

### Paso 4 · Registros DNS en Cloudflare (día 2)

En **DNS → Records**:

| Tipo | Nombre | Contenido | Proxy | Para qué |
| --- | --- | --- | --- | --- |
| A | `@` | IP del servidor | **DNS only** (nube gris) | La tienda |
| CNAME | `www` | `twistrolls.com` | **DNS only** | `www.twistrolls.com` |

**Deja el proxy apagado (nube gris) al lanzar.** Con el proxy de Cloudflare encendido, la app vería la IP de Cloudflare en vez de la del visitante: el contador de visitas juntaría a todos en uno y el límite de intentos del checkout se compartiría entre todos los clientes. Si más adelante quieres el proxy (caché y protección), primero hay que configurar en Laravel los rangos de IP de Cloudflare como *trusted proxies*. Es un cambio pequeño de código.

Los registros de correo están en el paso 9.

### Paso 5 · Crear el sitio en Forge (día 2)

1. En el servidor: **New site**, dominio `twistrolls.com`, alias `www.twistrolls.com`, tipo **Laravel**, *web directory* `/public`.
2. **Repository:** GitHub `diegoabdo/rolobox-tienda`, rama `master`. Deja activado *Install Composer dependencies*.
3. **Node:** revisa que el servidor tenga Node 22 o más nuevo.
4. **Environment:** reemplaza el `.env` por este, con tus valores:

```dotenv
APP_NAME=Twist & Rolls
APP_ENV=production
APP_DEBUG=false
APP_URL=https://twistrolls.com
APP_KEY=            # Forge la genera; si no, corre: php artisan key:generate

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=forge
DB_USERNAME=forge
DB_PASSWORD=        # la que te dio Forge

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=log

ROLOBOX_TIMEZONE=America/Guatemala
ROLOBOX_WHATSAPP=502XXXXXXXX
ROLOBOX_CONTACT_EMAIL=lorenaestrada@hotmail.com
ROLOBOX_LEGAL_ENTITY=Unique
ROLOBOX_LEGAL_VERSION=2026-11-01-v1
ROLOBOX_LEGAL_DRAFT=true          # false cuando los textos estén revisados
ROLOBOX_MIN_LEAD_DAYS=1

PAYMENTS_DRIVER=recurrente
RECURRENTE_SECRET_KEY=sk_test_...    # primero la de prueba (paso 8)
RECURRENTE_WEBHOOK_SECRET=whsec_...
RECURRENTE_BASE_URL=https://app.recurrente.com/api
```

5. **Deploy script:** reemplázalo por este:

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan optimize
$FORGE_PHP artisan inertia:stop-ssr || true   # el daemon lo vuelve a levantar con el código nuevo

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
```

Usa el despliegue normal de Forge (en el mismo directorio), **no** el de *zero downtime*: las fotos que subes desde el panel viven en `public/images/catalogo`, y con *zero downtime* cada despliegue arranca en una carpeta nueva sin ellas.

6. Pulsa **Deploy now** y revisa que termine sin errores.

### Paso 6 · SSL (día 2)

En el sitio de Forge: **SSL → Let's Encrypt**, para `twistrolls.com` y `www.twistrolls.com`. Se renueva solo. Luego, en Cloudflare, **SSL/TLS → Full (strict)**.

### Paso 7 · Procesos que deben quedar corriendo (día 2)

En Forge:

- **Daemons → New daemon** para el SSR:
  - Comando: `php8.4 artisan inertia:start-ssr`
  - Directorio: `/home/forge/twistrolls.com`
- **Scheduler:** activa el programador de tareas (en Forge: **Scheduler**, o un cron `* * * * * php8.4 /home/forge/twistrolls.com/artisan schedule:run`). Borra las visitas de más de un año.
- **Queue worker:** hoy la app no usa colas. No hace falta, y no estorba si lo agregas para el futuro.

### Paso 8 · Datos iniciales y panel (día 2–3)

Desde **Commands** en Forge (o por SSH, en la carpeta del sitio):

```bash
php artisan db:seed --force          # catálogo DE EJEMPLO: categorías, sabores, productos, zonas y la promoción
php artisan make:filament-user       # tu cuenta del panel
```

Luego entra a `https://twistrolls.com/admin` y:

1. En **Catálogo**, reemplaza o desactiva lo de ejemplo: Categorías, Sabores (con sus alérgenos y precios extra), Productos (precios reales) y Zonas (tarifas reales).
2. Revisa o desactiva la promoción **BIENVENIDA**.
3. Sube las fotos de categorías y sabores, si las tienes. Se guardan en `public/images/catalogo`, que debe poder escribirse (en Forge, el usuario `forge` ya es su dueño).
4. Deja la **Caja mixta de seis** desactivada hasta que quieras ofrecerla.

### Paso 9 · Correo con tu dominio (día 3, opcional pero recomendado)

Así los clientes escriben a `pedidos@twistrolls.com` y te llega a tu Hotmail. Cloudflare → **Email → Email Routing**:

1. Agrega el destino `lorenaestrada@hotmail.com` y confírmalo desde el correo que te envían.
2. Crea las direcciones `pedidos@twistrolls.com` y `contacto@twistrolls.com` → `lorenaestrada@hotmail.com`.
3. Cloudflare agrega solo los registros MX y SPF. Agrega también este para proteger el dominio contra suplantación:

| Tipo | Nombre | Contenido |
| --- | --- | --- |
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:lorenaestrada@hotmail.com` |

Si usas `pedidos@twistrolls.com` como correo público, cambia `ROLOBOX_CONTACT_EMAIL` en el `.env` del servidor. La tienda hoy no envía correos (`MAIL_MAILER=log`). Si algún día los envía (confirmaciones de pedido), hará falta un servicio de envío como Resend o Postmark y sus registros DKIM.

### Paso 10 · Conectar Recurrente (día 3)

1. **Primero en modo prueba:** usa la llave `sk_test_…` en el `.env`.
2. En Recurrente crea el webhook a `https://twistrolls.com/webhooks/recurrente` con el evento `intent.succeeded`, y copia su secreto en `RECURRENTE_WEBHOOK_SECRET`.
3. Haz dos compras de prueba:
   - `4242 4242 4242 4242`: debe aprobarse, el pedido pasa a **Pagado** en el panel y se ve en «Seguir mi pedido».
   - `4000 0000 0000 0002`: debe rechazarse y dejar reintentar.
4. Cuando la cuenta comercial esté aprobada: llaves **en vivo** (`sk_live_…`), webhook nuevo en modo en vivo con su secreto, y otra vez **Deploy**.

### Paso 11 · Revisión antes de anunciar (día 3–4)

- [ ] `https://twistrolls.com` y `https://www.twistrolls.com` cargan con candado.
- [ ] `http://` redirige a `https://`.
- [ ] Una compra real pequeña de punta a punta: caja → pago con tarjeta → pedido «Pagado» en el panel → «Seguir mi pedido» con el código RB.
- [ ] Una compra por transferencia.
- [ ] Precios, tarifas por zona, horarios y días sin reparto (`config/rolobox.php`) correctos.
- [ ] Términos y privacidad revisados, `ROLOBOX_LEGAL_DRAFT=false` y `ROLOBOX_LEGAL_VERSION` actualizada.
- [ ] El footer muestra Unique, lorenaestrada@hotmail.com y el WhatsApp correcto.
- [ ] El dashboard del panel empieza a mostrar visitas.
- [ ] La página de pago de prueba **no** existe: `https://twistrolls.com/pago-de-prueba/1` da 404.

## 5. Después del lanzamiento

- **Actualizar la tienda:** cada cambio que se sube a `master` se publica con **Deploy now**. También puedes activar *Quick deploy* en Forge para que se publique solo al hacer push.
- **Respaldos:** el Droplet se respalda cada semana. Además, conviene un respaldo diario de la base de datos fuera del servidor: un `mysqldump` programado a un bucket, o el plan Business de Forge, que lo incluye. **Prueba restaurar uno** al menos una vez.
- **Avisos de caída:** UptimeRobot (gratis) revisando `https://twistrolls.com/up` cada 5 minutos, con aviso a tu correo.
- **Errores:** en Forge, **Logs**, o `storage/logs/` en el servidor. Revisa los `critical`: indican un pago cuyo monto no coincidió.
- **Seguridad:** Forge aplica las actualizaciones de seguridad del sistema. Actualiza las dependencias (`composer update`, `npm update`) cada pocos meses, probando primero en local con `php artisan test`.

## 6. Calendario estimado

| Día | Qué |
| --- | --- |
| Antes | Pedir la cuenta comercial de Recurrente y la revisión legal (lo que más tarda) |
| 1 | Comprar el dominio y pasar el DNS a Cloudflare |
| 2 | Servidor, sitio, DNS, SSL, SSR y scheduler en Forge |
| 2–3 | Datos reales en el panel y correo con dominio |
| 3 | Recurrente en modo prueba y compras de prueba |
| 3–4 | Revisión final y lanzamiento con llaves en vivo |

Con el dominio y la cuenta de Recurrente listos, la parte técnica toma uno o dos días.

## Fuentes de los precios

- [Laravel Forge, precios 2026](https://laracopilot.com/blog/laravel-forge-pricing-2026/) y [reseña 2026](https://www.luckymedia.dev/insights/laravel-forge)
- [DigitalOcean, precios de Droplets 2026](https://infratally.com/articles/digitalocean-droplet-pricing-guide-2026/) y [costos de respaldos](https://zapro.ai/vendor/digitalocean-pricing/)
- [Laravel Cloud, planes y precios](https://cloud.laravel.com/docs/pricing)
- [Registro .gt, tarifas](https://www.gt/sitio/fees.php), [yo.gt](https://www.yo.gt/), [dominios.gt](https://www.dominios.gt/), [Grupo Intersat](https://grupointersat.com/registro-de-dominios-gt/)
- [Recurrente, precios](https://www.recurrente.com/precios)
