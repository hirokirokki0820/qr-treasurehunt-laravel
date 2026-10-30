<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ハズレを獲得上限の数に含めるかどうか（初期値は含めない）。
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('count_misses_toward_limit')->default(false)->after('max_prizes_per_participant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('count_misses_toward_limit');
        });
    }
};
