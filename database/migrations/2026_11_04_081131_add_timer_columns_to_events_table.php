<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 宝探しの制限時間（分）と、タイマーのスタート・終了の時刻。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedSmallInteger('time_limit_minutes')->nullable()->after('count_misses_toward_limit');
            $table->timestamp('started_at')->nullable()->after('time_limit_minutes');
            $table->timestamp('ends_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['time_limit_minutes', 'started_at', 'ends_at']);
        });
    }
};
