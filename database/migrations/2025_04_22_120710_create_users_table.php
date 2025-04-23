<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number')->unique();
            $table->string('email')->unique();
            $table->string('address');
            $table->string('password');
            $table->enum('role', ['admin', 'retailer', 'trader'])->default('retailer');
            $table->enum('status', ['verified', 'unverified', 'blocked', 'deactivated'])->default('unverified');
            $table->string('business_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->text('profile_picture')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};