<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            // Solo dígitos, con código de país (ej. 50255555555).
            $table->string('phone', 20)->unique();
            $table->string('email', 160)->nullable();
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique(); // Siempre en mayúsculas.
            $table->string('description', 200);
            $table->string('type', 20); // App\Enums\PromotionType
            $table->unsignedInteger('value')->default(0);
            $table->unsignedInteger('min_subtotal_cents')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->boolean('first_order_only')->default(false);
            $table->boolean('once_per_customer')->default(true);
            $table->string('applies_to', 20)->default('all'); // all | boxes
            $table->boolean('allow_national')->default(true);
            $table->boolean('show_banner')->default(false);
            $table->string('banner_title', 120)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('customers');
    }
};
