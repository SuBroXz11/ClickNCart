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
            $table->decimal('subtotal', 10, 2); // Changed from total_amount
            $table->decimal('tax', 10, 2);
            $table->decimal('shipping', 10, 2)->default(0); // Added
            $table->decimal('total', 10, 2); // Added
            $table->string('payment_method')->nullable(); // Added
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending'); // Changed from 'unpaid'
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('notes')->nullable(); // Added
            $table->string('transaction_id')->nullable(); // Added for PayPal
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};