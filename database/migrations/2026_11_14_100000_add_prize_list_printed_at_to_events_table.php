<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 緊急時（全員つながらないとき）に紙で続けるための「景品リスト」を、主催者が印刷ページで開いた時刻。
 * まだ開いていない、または開いたあとに景品を足したときは、イベントのページで印刷をすすめる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('prize_list_printed_at')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('prize_list_printed_at');
        });
    }
};
