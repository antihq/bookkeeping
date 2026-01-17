<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'amount']);
            $table->dropIndex(['team_id', 'account_id']);
            $table->dropIndex(['account_id']);
            $table->dropColumn('account_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->index();
            $table->index(['team_id', 'account_id']);
            $table->index(['account_id', 'amount']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropIndex(['team_id', 'account_id']);
            $table->dropIndex(['account_id', 'amount']);
            $table->dropColumn('account_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('account_id')->index();
            $table->index(['team_id', 'account_id']);
            $table->index(['account_id', 'amount']);
        });
    }
};
