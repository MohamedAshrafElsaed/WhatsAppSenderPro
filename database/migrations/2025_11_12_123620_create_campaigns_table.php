<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->foreignId('template_id')->nullable()->constrained('templates')->onDelete('set null');

            // Message content
            $table->enum('message_type', ['text', 'image', 'video', 'audio', 'document'])->default('text');
            $table->text('message_content');
            $table->text('message_caption')->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_url')->nullable();

            // Campaign status and scheduling
            $table->enum('status', ['draft', 'scheduled', 'running', 'paused', 'completed', 'failed'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Statistics
            $table->integer('total_recipients')->default(0);
            $table->integer('messages_sent')->default(0);
            $table->integer('messages_delivered')->default(0);
            $table->integer('messages_failed')->default(0);

            // WhatsApp session
            $table->string('session_id')->nullable(); // WhatsApp session ID to use for sending

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
