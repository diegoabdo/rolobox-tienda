<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flavors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->text('description');
            $table->string('allergens', 200)->nullable();
            $table->string('image_path')->nullable();
            // Solo los sabores que aguantan el viaje se ofrecen con envío por paquetería.
            $table->boolean('ships_nationally')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('type', 20); // App\Enums\ProductType
            $table->text('description');
            $table->unsignedTinyInteger('box_size')->nullable();
            $table->unsignedInteger('price_cents');
            $table->string('image_path')->nullable();
            $table->boolean('published')->default(false);
            $table->boolean('available')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('department', 60);
            $table->string('municipality', 80);
            $table->string('delivery_type', 20); // App\Enums\DeliveryType
            $table->unsignedInteger('fee_cents');
            $table->string('notes', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['delivery_type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
        Schema::dropIfExists('products');
        Schema::dropIfExists('flavors');
    }
};
