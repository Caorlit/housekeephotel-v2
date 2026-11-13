<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('attivo')->default(true)->after('ruolo');
        });

        // Avviso "hotel inattivo": si manda una volta sola per ogni periodo di inattività.
        Schema::table('hotels', function (Blueprint $table) {
            $table->timestamp('avviso_inattivita_il')->nullable()->after('attivo');
        });

        // Link degli inviti (validi 7 giorni), separati dai link "password dimenticata" (60 minuti).
        Schema::create('user_invitation_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitation_tokens');
        Schema::table('hotels', fn (Blueprint $t) => $t->dropColumn('avviso_inattivita_il'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('attivo'));
    }
};
