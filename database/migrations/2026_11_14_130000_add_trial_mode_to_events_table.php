<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * お試しモード：本番の前に、参加者3人までで会場の電波や流れを試す。
 * 終えると参加者・獲得の記録・タイマーを消して、同じイベント（印刷したQRもそのまま）で本番に入れる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('trial_mode')->default(false)->after('prize_list_printed_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('trial_mode');
        });
    }
};
