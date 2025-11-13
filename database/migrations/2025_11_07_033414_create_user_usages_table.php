<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('messages_sent')->default(0);
            $table->integer('contacts_validated')->default(0);
            $table->integer('connected_numbers_count')->default(0);
            $table->integer('templates_created')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('period_start');
            $table->index('period_end');
            $table->unique(['user_id', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_usage');
    }
};
