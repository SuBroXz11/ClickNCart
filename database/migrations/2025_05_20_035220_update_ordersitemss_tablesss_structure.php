<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_item_id')->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
             $table->string('product_id');
            $table->string('shop_id');
            $table->string('product_name');
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('total', 10, 2);
            $table->string('status')->default('pending');
            $table->string('cancel_requested')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->foreign('shop_id')
                  ->references('shop_id')
                  ->on('shops')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        
        Schema::dropIfExists('order_items');
    }
};