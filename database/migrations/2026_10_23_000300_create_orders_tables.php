<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            // Token secreto para ver el estado del pedido sin cuenta.
            $table->string('public_token', 64);
            // Evita pedidos duplicados si el cliente envía el checkout dos veces.
            $table->uuid('idempotency_key')->unique();

            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 20);
            $table->string('customer_email', 160)->nullable();
            $table->string('nit', 20)->default('CF');

            $table->string('delivery_type', 20);
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('zone_name', 160);
            $table->text('address');
            $table->string('address_reference', 255)->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('delivery_slot', 10)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promotion_code', 40)->nullable();
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('shipping_cents')->default(0);
            $table->unsignedInteger('total_cents');

            $table->string('status', 30)->default('pending_payment')->index();
            $table->timestamp('paid_at')->nullable();

            // Constancia legal: qué versión de términos y privacidad aceptó y cuándo.
            $table->string('legal_version', 40);
            $table->timestamp('accepted_at');

            // Conservación de datos (misma política que la versión anterior de Twist & Rolls).
            $table->char('record_key', 32)->unique();
            $table->timestamp('last_contact_at');
            $table->timestamp('closed_at')->nullable();
            $table->string('retention_hold', 30)->default('');

            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Copia del producto al momento de comprar: si después cambia el precio, el pedido no cambia.
            $table->string('product_name', 120);
            $table->string('product_type', 20);
            $table->json('flavors');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('line_total_cents');
            $table->string('gift_message', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
