<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Facoltativo: solo per il pulsante "Avvisa su WhatsApp" (camere urgenti).
            $table->string('telefono', 30)->nullable()->after('lingua');
        });
    }

    public function down(): void
    {
        Schema::table('staff', fn (Blueprint $t) => $t->dropColumn('telefono'));
    }
};
