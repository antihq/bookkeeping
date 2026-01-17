<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_account_id_amount_index');
            $table->dropIndex('transactions_team_id_account_id_index');
            $table->dropIndex('transactions_account_id_index');
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
            $table->dropIndex('transactions_account_id_index');
            $table->dropIndex('transactions_team_id_account_id_index');
            $table->dropIndex('transactions_account_id_amount_index');
            $table->dropColumn('account_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('account_id')->index();
            $table->index(['team_id', 'account_id']);
            $table->index(['account_id', 'amount']);
        });
    }
};
