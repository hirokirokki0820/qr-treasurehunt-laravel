<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 当日、つながらなくなって紙に切り替えた時刻。入っているあいだは、アプリで宝箱を開けられない（アプリと紙が混ざらないように）。
 * 片付けると空に戻る。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('paper_mode_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('paper_mode_at');
        });
    }
};
