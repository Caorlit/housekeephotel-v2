<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Addette: niente password né telefono, si entra con il token personale.
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->string('lingua', 2)->default('it'); // it en ro uk es bn
            $table->string('token_accesso', 64)->unique();
            $table->string('pin')->nullable(); // hash
            $table->boolean('attivo')->default(true);
            $table->timestamps();
        });

        // Anagrafica camere.
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('numero', 20);
            $table->smallInteger('piano')->default(0);
            $table->string('note_fisse')->nullable();
            $table->boolean('attiva')->default(true);
            $table->timestamps();

            $table->unique(['hotel_id', 'numero']);
        });

        // Stato di una camera in un giorno.
        Schema::create('room_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('data');
            $table->string('tipo', 20)->default('partenza');  // partenza | fermata | extra
            $table->string('stato', 20)->default('da_fare');  // da_fare | in_pulizia | pronta
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->boolean('urgente')->default(false);
            $table->text('nota_reception')->nullable();
            $table->timestamp('iniziata_alle')->nullable();
            $table->timestamp('finita_alle')->nullable();
            // Momento (secondo il dispositivo) dell'ultimo cambio di stato:
            // serve a scartare le azioni offline arrivate in ritardo.
            $table->timestamp('stato_cambiato_alle', 3)->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'data']);
            $table->index(['hotel_id', 'data']);
            $table->index(['staff_id', 'data']);
            $table->index(['hotel_id', 'updated_at']);
        });

        // Segnalazioni (pulsante rosso).
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('nota')->nullable();
            $table->string('foto_path')->nullable();
            $table->string('stato', 20)->default('aperta'); // aperta | presa_in_carico | risolta
            $table->uuid('client_action_id')->nullable()->unique();
            $table->timestamp('letta_alle')->nullable();
            $table->timestamp('risolta_alle')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'stato']);
            $table->index(['hotel_id', 'updated_at']);
        });

        // Registro di tutto ciò che succede.
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_day_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo', 40);
            $table->json('payload')->nullable();
            // Idempotenza delle azioni dal telefono.
            $table->uuid('client_action_id')->nullable()->unique();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['hotel_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('issues');
        Schema::dropIfExists('room_days');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('staff');
    }
};
