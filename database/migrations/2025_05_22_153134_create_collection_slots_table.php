<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collection_slots', function (Blueprint $table) {
            $table->id();
            $table->string('slot_id')->unique();
            // Change foreignId to string to match shops.shop_id type
            $table->foreignId('order_id')->nullable()->constrained('orders', 'id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('special_instructions')->nullable();
            $table->string('status')->default('available'); // available, booked, collected, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_slots');
    }
};