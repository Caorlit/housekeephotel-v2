<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_days', function (Blueprint $table) {
            // true = assegnata a una sostituta (giorno libero della titolare):
            // l'assegnazione automatica dei giorni successivi la ignora.
            $table->boolean('sostituzione')->default(false)->after('staff_id');
        });
    }

    public function down(): void
    {
        Schema::table('room_days', function (Blueprint $table) {
            $table->dropColumn('sostituzione');
        });
    }
};
