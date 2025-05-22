<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->json('shipping_address')->nullable(); // Changed from text to json
            $table->json('billing_address')->nullable();  // Changed from text to json
            $table->text('notes')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('collection_slot_id')->nullable(); // Added
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};