<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 参加者カード（スマホがない子用）。主催者が作って印刷し、主催者のスマホで「カード → お宝」の順に読み取る。
 * card_token はカードのQRに入る合言葉。カードではない（自分のスマホで参加した）参加者は空。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('card_token', 40)->nullable()->unique()->after('redeem_token');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique(['card_token']);
            $table->dropColumn('card_token');
        });
    }
};
