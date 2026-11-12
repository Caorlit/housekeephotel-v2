<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('indirizzo')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('pin_richiesto')->default(false);
            $table->string('fuso_orario', 64)->default('Europe/Rome');
            $table->boolean('attivo')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // null solo per il superadmin
            $table->foreignId('hotel_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('ruolo', 20)->default('reception'); // superadmin | reception
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('hotels');
    }
};
