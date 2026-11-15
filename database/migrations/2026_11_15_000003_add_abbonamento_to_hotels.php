<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Abbonamento gestito a mano: prova di 3 mesi, poi rinnovo annuale.
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('piano', 10)->nullable()->after('attivo');        // small | medium | large
            $table->date('prova_fino_al')->nullable()->after('piano');
            $table->date('pagato_fino_al')->nullable()->after('prova_fino_al');
            $table->string('avviso_scadenza', 30)->nullable()->after('pagato_fino_al'); // es. "2027-02-15:preavviso"
        });

        foreach (DB::table('hotels')->get(['id', 'created_at']) as $h) {
            DB::table('hotels')->where('id', $h->id)->update([
                'prova_fino_al' => Carbon::parse($h->created_at ?? now())->addMonthsNoOverflow(3)->toDateString(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('hotels', fn (Blueprint $t) => $t->dropColumn(['piano', 'prova_fino_al', 'pagato_fino_al', 'avviso_scadenza']));
    }
};
