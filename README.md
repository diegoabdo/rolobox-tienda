# Twist & Rolls · Tienda en línea

Tienda de roles de canela por encargo en Quetzaltenango (Xela). Los clientes piden roles sueltos desde el menú o arman una caja de seis, pagan con tarjeta o transferencia en la página segura de Recurrente y siguen su pedido con un código RB. El negocio lo administra desde un panel interno.

Twist & Rolls es una marca de **Unique**. Contacto para pedidos y consultas: **lorenaestrada@hotmail.com**.

Laravel 13 + Inertia 3 + React 19 + Tailwind 4 (con SSR) y panel Filament 5.

Para publicar la tienda en internet, sigue [DESPLIEGUE.md](DESPLIEGUE.md).

## Qué tiene

**Tienda** (`resources/js/pages/`)

- **Inicio:** menú con los seis sabores para pedir roles sueltos, precios de caja y rol, cómo funciona, zonas de reparto en Xela con sus tarifas, búsqueda de pedido y preguntas frecuentes.
- **Arma tu caja:** se eligen seis roles y la caja se llena en el orden elegido. Admite un mensaje para la tarjeta de regalo. Desde el menú, «Ponlo en una caja» abre esta página con el sabor ya puesto.
- **Carrito y pago («Tu pedido»):** zona, dirección, día y horario de entrega, datos de contacto, cupón y forma de pago (tarjeta o transferencia, con su total cada una). El resumen muestra los roles elegidos.
- **Seguir mi pedido:** con el código RB y el WhatsApp del pedido.
- **Términos y privacidad:** en `resources/legal/`, con el nombre de la empresa y el correo tomados del `.env`.

**Panel interno** en `/admin` (Filament 5)

- Pedidos (con botones para avanzar el estado), ventas en persona, productos, sabores, zonas, promociones, gastos y cierre de caja.
- Dashboard: ventas, entregas próximas, cuántos roles hornear por sabor y **visitas a la tienda** (ver abajo).
- Reporte mensual con ganancia neta.

**Estadísticas de visitas** (`app/Http/Middleware/RecordPageView.php`)

- Cuenta las páginas vistas en tu propia base de datos, sin cookies, sin servicios externos y sin guardar la IP. Cada visitante se identifica con una huella anónima que cambia cada día, así que «visitantes» significa visitantes por día.
- No cuenta bots, visitas con sesión iniciada en el admin, recargas internas del checkout ni precargas.
- En el dashboard: visitantes de hoy, de 7 y de 30 días, porcentaje que compra, recorrido hasta pagar, páginas más vistas y de dónde llegan.
- Las visitas de más de un año se borran solas con `model:prune`, programado a diario en `routes/console.php`.

## Requisitos

- PHP 8.3 o más nuevo, con las extensiones `pdo_sqlite` (o `pdo_mysql`), `mbstring`, `intl`, `curl` y `gd`
- Composer 2
- Node.js 22 o más nuevo

## Primera vez

```bash
composer install
cp .env.example .env          # en PowerShell: copy .env.example .env
php artisan key:generate
php artisan migrate --seed    # crea database/database.sqlite y carga el catálogo de ejemplo
npm install
php artisan make:filament-user  # tu cuenta para entrar a /admin
```

Después, para trabajar:

```bash
composer run dev              # servidor PHP, colas, logs y Vite juntos
```

- Tienda: http://localhost:8000
- Panel: http://localhost:8000/admin. No hay botón en la tienda a propósito: se entra escribiendo la dirección.

> **Precios, tarifas y la promoción BIENVENIDA son de ejemplo** (`database/seeders/CatalogSeeder.php`). Cámbialos desde el panel antes de publicar.

## Configuración del negocio (`.env`)

| Variable | Para qué |
| --- | --- |
| `ROLOBOX_CONTACT_EMAIL` | Correo de pedidos y consultas: footer, términos y privacidad |
| `ROLOBOX_LEGAL_ENTITY` | Empresa responsable (Unique), en los textos legales y el footer |
| `ROLOBOX_WHATSAPP` | Número para el enlace de WhatsApp, con código de país (502…) |
| `ROLOBOX_LEGAL_VERSION` | Versión de términos y privacidad que acepta cada pedido. Súbela al cambiar los textos |
| `ROLOBOX_LEGAL_DRAFT` | `true` muestra el aviso «Borrador en revisión» en las páginas legales |
| `ROLOBOX_MIN_LEAD_DAYS` | Días mínimos de anticipación para entregar |
| `PAYMENTS_DRIVER` | `fake` (pago simulado, solo en local) o `recurrente` |

Horarios de reparto y días sin entrega: `config/rolobox.php`.

## Probar un pago sin tarjeta

Con `PAYMENTS_DRIVER=fake` (el valor por defecto en `.env.example`), el botón «Pagar» abre una página de pago simulada con los botones «Pagar» y «Cancelar». Recorre exactamente el mismo camino que un pago real: confirmación, página del pedido y uso del cupón. Esa página no existe en producción.

## Pruebas

```bash
php artisan test
```

Cubren: armado de cajas, totales calculados en el servidor, cupones (primera compra, un uso por teléfono), pedidos duplicados, token secreto de la página del pedido, búsqueda por código + teléfono, firma de los webhooks, confirmación de pago idempotente, finanzas, reportes, el panel y el contador de visitas.

## Conectar Recurrente

1. En Recurrente, copia tu llave secreta de prueba (`sk_test_…`).
2. En `.env`:

   ```
   PAYMENTS_DRIVER=recurrente
   RECURRENTE_SECRET_KEY=sk_test_...
   ```

3. Crea un endpoint de webhooks que apunte a `https://TU-DOMINIO/webhooks/recurrente` con el evento `intent.succeeded`, y copia su secreto (`whsec_…`) en `RECURRENTE_WEBHOOK_SECRET`.
4. Prueba con las tarjetas de prueba de Recurrente: `4242 4242 4242 4242` (aprobada) y `4000 0000 0000 0002` (rechazada).

Cómo se confirma un pago: ni el regreso del cliente a la tienda ni el contenido del webhook se toman como prueba. En los dos casos el servidor le pregunta a Recurrente por el checkout y solo marca el pedido como pagado si responde `paid` **y** el monto coincide con el total del pedido. Si el monto no coincide, no lo confirma y deja un registro `critical` en el log.

En local el webhook no llega (Recurrente no puede ver tu computadora), pero la confirmación igual funciona porque se consulta al volver de la página de pago.

## Producción, en resumen

El paso a paso completo, con costos y DNS, está en [DESPLIEGUE.md](DESPLIEGUE.md).

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build       # compila el cliente y el SSR
php artisan migrate --force
php artisan optimize          # cachea configuración, rutas y vistas
php artisan inertia:start-ssr # proceso aparte, siempre encendido (daemon)
```

En el servidor también deben correr:

- **El programador de tareas:** `* * * * * php artisan schedule:run`, para borrar las visitas viejas.
- **El SSR de Inertia:** como daemon.

## Diseño

- Colores tomados del producto (chocolate, glaseado, caramelo y kraft) como variables de Tailwind en `resources/css/app.css`.
- Tipografía Schibsted Grotesk (licencia OFL), servida desde el propio sitio en `resources/fonts/`, sin conexión a Google.
- El círculo, el rol visto desde arriba, se repite como motivo: fotos, días de entrega, el ticket del pago y el footer.
- Las fotos de cada sabor son recortes de `public/images/caja.jpg`. Para reemplazarlas, sube fotos nuevas desde el panel (Sabores).
- Foto de Quetzaltenango: «Panoramica de Quetzaltenango» de Jvito3312, CC BY-SA 4.0, vía Wikimedia Commons. El crédito está en el footer y en `public/images/credits.json`.

## Dónde está cada cosa

| Qué | Dónde |
| --- | --- |
| Rutas | `routes/web.php` |
| Carrito (en la sesión) | `app/Services/Cart.php` |
| Totales y cupones | `app/Services/Pricing.php` |
| Crear pedido y link de pago | `app/Services/Checkout.php` |
| Recurrente y procesador simulado | `app/Services/Payments/` |
| Confirmar pagos (único lugar) | `app/Services/Payments/OrderPayments.php` |
| Webhook | `app/Http/Controllers/Webhooks/RecurrenteWebhookController.php` |
| Contador de visitas | `app/Http/Middleware/RecordPageView.php`, `app/Models/PageView.php` |
| Widgets del dashboard | `app/Filament/Widgets/` |
| Configuración del negocio | `config/rolobox.php` y `.env` |
| Páginas de React | `resources/js/pages/` |
| Diseño (colores, tipos, animaciones) | `resources/css/app.css` |
| Textos legales | `resources/legal/` |

## Notas del panel

- Las cuentas del panel se crean con `php artisan make:filament-user`; no hay registro público. Hoy cualquier cuenta puede entrar al panel (`canAccessPanel` en `app/Models/User.php`); si algún día hay cuentas de clientes, limítalo a administradores.
- Los pedidos no se crean, editan ni borran desde el panel: solo se consultan y se cambia su estado (las ventas en persona sí se registran ahí).
- Productos, sabores, zonas y promociones no se borran: se desactivan, para no romper pedidos ni el historial de cupones.
- Las fotos subidas se guardan en `public/images/catalogo` (disco `catalog`, fuera de git). En un servidor con disco efímero hay que pasarlas a S3/R2.

## Pendiente

- **Textos legales:** están como borrador (`ROLOBOX_LEGAL_DRAFT=true`). Hazlos revisar, pon `ROLOBOX_LEGAL_DRAFT=false` y sube `ROLOBOX_LEGAL_VERSION` al publicarlos.
- **Limpieza automática de pedidos:** los pedidos ya guardan `record_key`, `last_contact_at`, `closed_at` y `retention_hold`. Falta el comando programado que aplica los 90 días y los 12 meses del aviso de privacidad, y que marca como vencidos los pedidos sin pagar.
- **Envío nacional:** las zonas existen pero están inactivas.
- **Sección «Del horno a tu puerta»:** el dibujo de puntos está por reemplazarse por fotos del proceso o dibujos.
- Avisos por WhatsApp y factura FEL.
