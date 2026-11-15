<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Richieste "Prova gratis" arrivate dal sito housekeephotel.com.
        Schema::create('trial_requests', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('hotel', 150);
            $table->string('citta', 100)->nullable();
            $table->string('email');
            $table->string('telefono', 40)->nullable();
            $table->unsignedSmallInteger('camere')->nullable();
            $table->text('messaggio')->nullable();
            $table->string('lingua', 2)->default('it');
            $table->string('stato', 20)->default('nuova'); // nuova | contattata | attivata | scartata
            $table->foreignId('hotel_id')->nullable()->constrained()->nullOnDelete(); // hotel creato dalla richiesta
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_requests');
    }
};
