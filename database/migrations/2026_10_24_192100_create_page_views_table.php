<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visitas a la tienda, sin cookies ni datos personales: la página por su nombre de ruta,
 * una huella anónima que cambia cada día y el sitio desde el que llegó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            // Nombre de la ruta (home, box, checkout.show…), nunca la URL: así no quedan códigos de pedido.
            $table->string('page', 60);
            // sha256 de la fecha, la IP y el navegador con la llave de la app; no se puede revertir ni seguir entre días.
            $table->char('visitor', 64);
            $table->string('referrer', 120)->nullable();
            $table->boolean('is_mobile')->default(false);
            $table->timestamp('viewed_at')->index();

            $table->index(['viewed_at', 'visitor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
