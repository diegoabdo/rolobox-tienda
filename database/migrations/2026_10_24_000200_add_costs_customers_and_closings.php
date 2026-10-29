<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Costos para calcular ganancias reales, notas de clientes y cierres de caja.
 *
 * Los pedidos guardan una copia de sus costos al momento de la venta: si después cambia
 * el costo de un ingrediente, los reportes de meses anteriores no cambian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flavors', function (Blueprint $table) {
            // Lo que cuesta producir un rol de este sabor (ingredientes).
            $table->unsignedInteger('cost_cents')->default(0)->after('ships_nationally');
        });

        Schema::table('products', function (Blueprint $table) {
            // Empaque por unidad vendida: la caja, la bolsa, la etiqueta.
            $table->unsignedInteger('packaging_cost_cents')->default(0)->after('transfer_price_cents');
        });

        Schema::table('zones', function (Blueprint $table) {
            // Lo que le cuesta a Twist & Rolls entregar en esta zona (gasolina, mensajero).
            $table->unsignedInteger('delivery_cost_cents')->default(0)->after('fee_cents');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('cost_cents')->nullable()->after('line_total_cents');
        });

        Schema::table('orders', function (Blueprint $table) {
            // null = pedido anterior a que existieran los costos.
            $table->unsignedInteger('cost_cents')->nullable()->after('total_cents');
            $table->unsignedInteger('delivery_cost_cents')->nullable()->after('cost_cents');
            // Comisión estimada del procesador; se calcula al confirmar el pago.
            $table->unsignedInteger('payment_fee_cents')->nullable()->after('delivery_cost_cents');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('email');
        });

        Schema::create('cash_closings', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');

            // Copia de los números del periodo al momento del cierre.
            $table->unsignedInteger('orders_count');
            $table->unsignedInteger('gross_cents');
            $table->unsignedInteger('card_cents');
            $table->unsignedInteger('transfer_cents');
            $table->unsignedInteger('fees_cents');
            $table->unsignedInteger('expected_deposit_cents');
            $table->unsignedInteger('cost_cents');
            $table->unsignedInteger('delivery_cost_cents');
            $table->integer('profit_cents');

            // Lo que realmente llegó a la cuenta, contado por quien cierra.
            $table->unsignedInteger('counted_cents')->nullable();
            $table->integer('difference_cents')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_closings');
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('notes'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['cost_cents', 'delivery_cost_cents', 'payment_fee_cents']));
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('cost_cents'));
        Schema::table('zones', fn (Blueprint $table) => $table->dropColumn('delivery_cost_cents'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('packaging_cost_cents'));
        Schema::table('flavors', fn (Blueprint $table) => $table->dropColumn('cost_cents'));
    }
};
