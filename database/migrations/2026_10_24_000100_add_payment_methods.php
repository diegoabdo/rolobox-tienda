<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Precio distinto según cómo paga el cliente.
 *
 * La Ley de Tarjetas de Crédito (Decreto 2-2024, art. 33) prohíbe cobrar recargo por pagar con
 * tarjeta. Por eso el precio publicado (price_cents) es el de tarjeta, y transfer_price_cents es
 * un precio con descuento para quien paga por transferencia, anunciado de forma visible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // null = mismo precio que con tarjeta.
            $table->unsignedInteger('transfer_price_cents')->nullable()->after('price_cents');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Método que eligió el cliente al pagar (App\Enums\PaymentMethod); define qué precios se usaron.
            $table->string('payment_method', 20)->default('card')->after('status');
        });

        Schema::table('payments', function (Blueprint $table) {
            // Método con el que realmente se pagó, según el procesador.
            $table->string('method', 30)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('method'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('payment_method'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('transfer_price_cents'));
    }
};
