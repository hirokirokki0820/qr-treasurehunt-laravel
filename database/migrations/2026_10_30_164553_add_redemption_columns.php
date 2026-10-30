<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * 景品の引き換え記録と、参加者ごとの引き換え用QRコードのトークンを追加する。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->timestamp('redeemed_at')->nullable()->after('acquired_at');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->string('redeem_token', 40)->nullable()->unique()->after('nickname');
        });

        DB::table('participants')->whereNull('redeem_token')->orderBy('id')->each(function (object $participant): void {
            DB::table('participants')->where('id', $participant->id)->update(['redeem_token' => Str::random(40)]);
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique(['redeem_token']);
            $table->dropColumn('redeem_token');
        });

        Schema::table('prizes', function (Blueprint $table) {
            $table->dropColumn('redeemed_at');
        });
    }
};
