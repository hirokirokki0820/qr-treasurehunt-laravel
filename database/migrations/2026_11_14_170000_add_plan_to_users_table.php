<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 主催者の利用の区分。「kobapp でログイン」のたびに、kobapp.jp から受け取った値で書き換える。
 * free（参加者3人まで）・monitor（採用されたモニター、100人まで）・admin（運営者、上限なし）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan', 20)->default('free')->after('kobapp_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('plan');
        });
    }
};
