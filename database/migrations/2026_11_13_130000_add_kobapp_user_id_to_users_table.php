<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 主催者は「kobapp でログイン」で入る。kobapp のユーザーIDで見分け、アカウント名とパスワードは使わない。
     * テスト版のアカウント（kobapp_user_id が空）は引き継がない。
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kobapp_user_id', 64)->nullable()->unique()->after('id');
            $table->string('name')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['kobapp_user_id']);
            $table->dropColumn('kobapp_user_id');
        });
    }
};
