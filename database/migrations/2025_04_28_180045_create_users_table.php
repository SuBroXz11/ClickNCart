<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->string('email')->unique();
            $table->string('address')->nullable();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('status')->default('active');
            $table->string('business_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('business_registration_image')->nullable();
            $table->string('email_verification_code')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};