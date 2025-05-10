<?php

// database/migrations/2024_06_01_create_products_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_id')->unique();
            $table->foreignId('retailer_id')->constrained('users');
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('category');
            $table->string('subcategory');
            $table->string('brand');
            $table->integer('stock_quantity');
            $table->json('images');
            $table->json('ratings')->nullable();
            $table->json('variants');
            $table->json('specifications');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance on filters
            $table->index('category');
            $table->index('subcategory');
            $table->index('brand');
            $table->index('price');
            $table->index('is_active');
            $table->index('is_featured');
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};