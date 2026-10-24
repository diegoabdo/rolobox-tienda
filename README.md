# RoloBox · Tienda en línea

Tienda pública de RoloBox: menú, armado de cajas, carrito, checkout con cupones y pago con tarjeta en la página segura de Recurrente. Laravel 13 + Inertia 3 + React 19 + Tailwind 4, con SSR.

Todavía **no** incluye: panel de administración (Filament), seguimiento GPS, envío nacional activo, avisos por WhatsApp, factura FEL ni la limpieza automática de datos. Ver «Pendiente» al final.

## Requisitos

- PHP 8.3 o más nuevo, con las extensiones `pdo_sqlite`, `mbstring`, `intl` y `curl`
- Composer 2
- Node.js 22 o más nuevo

## Primera vez

```bash
composer install
cp .env.example .env          # en PowerShell: copy .env.example .env
php artisan key:generate
php artisan migrate --seed    # crea database/database.sqlite y carga el catálogo de ejemplo
npm install
```

Después, para trabajar:

```bash
composer run dev              # servidor PHP, colas, logs y Vite juntos
```

Abre http://localhost:8000.

> **Precios, tarifas y la promoción BIENVENIDA son de ejemplo** (`database/seeders/CatalogSeeder.php`). Cámbialos antes de publicar.

## Probar un pago sin tarjeta

Con `PAYMENTS_DRIVER=fake` (el valor por defecto en `.env.example`), el botón «Pagar con tarjeta» abre una página de pago simulada con los botones «Pagar» y «Cancelar». Recorre exactamente el mismo camino que un pago real: confirmación, página del pedido y uso del cupón. Esa página no existe en producción.

## Pruebas

```bash
php artisan test
```

Cubren: armado de cajas, totales calculados en el servidor, cupones (primera compra, un uso por teléfono), pedidos duplicados, token secreto de la página del pedido, búsqueda por código + teléfono, firma de los webhooks y confirmación de pago idempotente.

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

## Producción

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build       # compila el cliente y el SSR
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan inertia:start-ssr # proceso aparte (supervisor o systemd)
```

En `.env` de producción: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, `PAYMENTS_DRIVER=recurrente` y la base de datos (PostgreSQL o MySQL). La app se niega a usar el procesador simulado en producción.

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
| Configuración del negocio (horarios, días sin reparto, versión legal) | `config/rolobox.php` |
| Páginas de React | `resources/js/pages/` |
| Textos legales (borrador) | `resources/legal/` |

## Pendiente

- **Panel Filament** para productos, zonas, promociones y estados de pedido. Hoy se cargan con el seeder o con `php artisan tinker`.
- **Limpieza automática de datos**: los pedidos ya guardan `record_key`, `last_contact_at`, `closed_at` y `retention_hold`. Falta el comando programado que aplica los 90 días y los 12 meses, y que marca como vencidos los pedidos sin pagar.
- **Textos legales**: están como borrador (`ROLOBOX_LEGAL_DRAFT=true`). Completa `ROLOBOX_LEGAL_ENTITY` y el correo, hazlos revisar y cambia `ROLOBOX_LEGAL_VERSION` al publicarlos.
- **Envío nacional**: las zonas existen pero están inactivas.
- Avisos por WhatsApp y factura FEL.

## Panel interno (Filament 5)

En `/admin`. Pedidos (con botones para avanzar el estado), productos, sabores, zonas y promociones, y un dashboard con ventas, entregas próximas y cuántos roles de cada sabor hornear.

```
composer require filament/filament:"^5.0" -W
php artisan make:filament-user
```

- Las cuentas del panel se crean con `make:filament-user`; no hay registro público.
- Los pedidos no se crean, editan ni borran desde el panel: solo se consultan y se cambia su estado.
- Productos, sabores, zonas y promociones no se borran: se desactivan, para no romper pedidos ni el historial de cupones.
- Las fotos subidas se guardan en `public/images/catalogo` (disco `catalog`). En un servidor con disco efímero hay que pasarlas a S3/R2.
