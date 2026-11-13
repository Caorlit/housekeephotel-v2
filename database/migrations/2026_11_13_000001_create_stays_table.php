<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prenotazioni (soggiorni). Niente nome dell'ospite né prezzo:
        // alle pulizie servono solo camera, date, frequenza del riassetto e persone.
        Schema::create('stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('arrivo');
            $table->date('partenza');
            // Ogni quanti giorni il riassetto (0 = nessuno fino alla partenza).
            $table->unsignedTinyInteger('riassetto_ogni')->default(2);
            $table->string('riferimento', 100)->nullable(); // codice prenotazione del gestionale
            $table->unsignedTinyInteger('pax')->nullable();
            $table->string('nota', 255)->nullable();
            $table->string('fonte', 10)->default('manuale'); // manuale | csv
            $table->timestamps();

            $table->index(['hotel_id', 'arrivo', 'partenza']);
            $table->index(['room_id', 'arrivo']);
            $table->unique(['hotel_id', 'room_id', 'riferimento']);
        });

        Schema::table('room_days', function (Blueprint $table) {
            // Da quale prenotazione è nato (null = inserita a mano).
            $table->foreignId('stay_id')->nullable()->after('room_id')->constrained()->nullOnDelete();
        });

        // Giorni già generati dalle prenotazioni: le modifiche a mano successive restano.
        Schema::create('day_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->date('data');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['hotel_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_generations');
        Schema::table('room_days', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stay_id');
        });
        Schema::dropIfExists('stays');
    }
};
