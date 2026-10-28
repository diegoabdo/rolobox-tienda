<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La tienda deja de ser solo de roles de canela: categorías (roles, croissants, focaccias, trenzas…),
 * cada sabor pertenece a una categoría y puede ir en cajas propias o mixtas, y cada producto
 * (presentación o caja) pertenece a una categoría.
 *
 * Todo lo que ya existe pasa a la categoría «Rolls de canela», así los pedidos y carritos viejos siguen igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('description', 255)->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('flavors', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            // Precio extra de este sabor sobre el de la presentación (ej.: croissant de almendra +Q3).
            $table->unsignedInteger('extra_cents')->default(0)->after('cost_cents');
            // Puede ir en las cajas de su categoría y en las cajas mixtas.
            $table->boolean('box_eligible')->default(true)->after('ships_nationally');
            $table->boolean('mix_eligible')->default(false)->after('box_eligible');
        });

        Schema::table('products', function (Blueprint $table) {
            // Una caja mixta no tiene categoría propia.
            $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            // category = la caja lleva solo sabores de su categoría; mixed = mezcla de varias categorías.
            $table->string('box_scope', 20)->default('category')->after('box_size');
        });

        $now = now();
        $rollsId = DB::table('categories')->insertGetId([
            'name' => 'Rolls de canela',
            'slug' => 'rolls-de-canela',
            'description' => 'Masa suave, canela y glaseados. Pídelos sueltos o en caja de seis.',
            'image_path' => 'images/caja.jpg',
            'active' => true,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('flavors')->update(['category_id' => $rollsId, 'box_eligible' => true, 'mix_eligible' => true]);
        DB::table('products')->update(['category_id' => $rollsId]);
        DB::table('products')->where('slug', 'rol-suelto')->where('name', 'Rol suelto')->update(['name' => 'Rol']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('box_scope');
        });

        Schema::table('flavors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['extra_cents', 'box_eligible', 'mix_eligible']);
        });

        Schema::dropIfExists('categories');
    }
};
