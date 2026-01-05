<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->index();
            $table->foreignId('team_id')->index();
            $table->foreignId('created_by');
            $table->foreignId('category_id')->index();
            $table->date('date');
            $table->string('title');
            $table->string('note')->nullable();
            $table->integer('amount');
            $table->timestamps();

            $table->index(['team_id', 'account_id']);
            $table->index(['team_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
