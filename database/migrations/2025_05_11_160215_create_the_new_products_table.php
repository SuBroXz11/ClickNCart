<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_id')->unique();
            $table->string('shop_id'); // Foreign key to shops table3
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('category');
            $table->string('subcategory');
            $table->string('brand');
            $table->integer('stock_quantity')->default(0);
            $table->json('images')->nullable();
            $table->json('ratings')->nullable()->comment('JSON containing average and count');
            $table->json('variants')->nullable()->comment('Product variants data');
            $table->json('specifications')->nullable()->comment('Product specifications');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint
            $table->foreign('shop_id')
                  ->references('shop_id')
                  ->on('shops')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
        });

        Schema::dropIfExists('products');
    }
};