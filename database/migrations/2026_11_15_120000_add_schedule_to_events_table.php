<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * イベントの開催予定（日本時間の開始日時と、開催時間の分）。
 * requires_schedule：これから作るイベントは true（本番の前に予定が必要）。前からあるイベントは false のまま、今までどおり動く。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('scheduled_start_at')->nullable()->after('time_limit_minutes');
            $table->unsignedSmallInteger('scheduled_minutes')->nullable()->after('scheduled_start_at');
            $table->boolean('requires_schedule')->default(false)->after('scheduled_minutes');
            $table->index('scheduled_start_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['scheduled_start_at']);
            $table->dropColumn(['scheduled_start_at', 'scheduled_minutes', 'requires_schedule']);
        });
    }
};
