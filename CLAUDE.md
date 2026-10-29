# Twist & Rolls

Tienda en línea de roles de canela por encargo en Xela, Guatemala. Laravel 13, Inertia 3, React 19, Tailwind 4 y panel Filament 5. El README explica cómo correrla y `DESPLIEGUE.md` cómo publicarla.

- No instalar Laravel Boost ni ejecutar `boost:install`: se quitó a propósito.
- Las skills de Claude viven en `~/.claude/skills`, no en el proyecto: no copiarlas aquí.
- Antes de terminar un cambio: `php artisan test` y `npm run types`. Si se tocó PHP, `vendor/bin/pint --dirty`.
- Los nombres internos (`config/rolobox.php`, variables `ROLOBOX_*`) conservan «rolobox» a propósito; la marca visible es «Twist & Rolls».
