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
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');

            // Sending status
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('error_message')->nullable();

            // Message details
            $table->string('message_id')->nullable(); // WhatsApp message ID
            $table->text('personalized_message')->nullable(); // Message after placeholder replacement

            $table->timestamps();

            // Indexes
            $table->index('campaign_id');
            $table->index('contact_id');
            $table->index('status');
            $table->unique(['campaign_id', 'contact_id']); // Prevent duplicate recipients
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
