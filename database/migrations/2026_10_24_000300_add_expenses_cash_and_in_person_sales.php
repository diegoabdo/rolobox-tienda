<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventas en persona (efectivo o POS), gastos del negocio y cierre de caja diario con conteo de efectivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // online = tienda en línea; in_person = venta registrada a mano en el panel.
            $table->string('channel', 20)->default('online')->after('status');
        });

        // Una venta en persona puede no tener cliente registrado.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->change();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('spent_on')->index();
            $table->string('category', 30);
            $table->string('description', 200);
            $table->unsignedInteger('amount_cents');
            // Cómo se pagó: el efectivo cuenta en el cierre de caja.
            $table->string('payment_method', 20)->default('cash');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('cash_closings', function (Blueprint $table) {
            $table->unsignedInteger('cash_sales_cents')->default(0)->after('transfer_cents');
            $table->unsignedInteger('in_person_cents')->default(0)->after('cash_sales_cents');
            $table->unsignedInteger('expenses_cents')->default(0)->after('profit_cents');
            $table->unsignedInteger('cash_expenses_cents')->default(0)->after('expenses_cents');
            $table->integer('net_profit_cents')->default(0)->after('cash_expenses_cents');

            // Caja de efectivo: fondo con que se abrió + ventas en efectivo − gastos en efectivo.
            $table->unsignedInteger('opening_cash_cents')->default(0)->after('net_profit_cents');
            $table->integer('expected_cash_cents')->default(0)->after('opening_cash_cents');
            $table->unsignedInteger('counted_cash_cents')->nullable()->after('expected_cash_cents');
            $table->integer('cash_difference_cents')->nullable()->after('counted_cash_cents');
        });
    }

    public function down(): void
    {
        Schema::table('cash_closings', fn (Blueprint $table) => $table->dropColumn([
            'cash_sales_cents', 'in_person_cents', 'expenses_cents', 'cash_expenses_cents', 'net_profit_cents',
            'opening_cash_cents', 'expected_cash_cents', 'counted_cash_cents', 'cash_difference_cents',
        ]));
        Schema::dropIfExists('expenses');
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('channel'));
    }
};
