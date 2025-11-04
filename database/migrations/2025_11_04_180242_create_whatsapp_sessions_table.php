<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // WhatsApp Sessions table (main table - create first)
        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('session_name', 255);
            $table->string('phone_number', 20)->nullable();
            $table->string('j_id', 255)->nullable()->unique(); // Changed from jid to j_id, size 255
            $table->string('status', 50)->default('pending'); // Changed from enum to string(50)

            // QR Code fields
            $table->text('qr_code')->nullable();
            $table->text('qr_code_base64')->nullable(); // NEW FIELD from Go struct
            $table->timestamp('qr_generated_at')->nullable();
            $table->timestamp('qr_expires_at')->nullable();
            $table->unsignedInteger('qr_retry_count')->default(0); // Changed from unsignedSmallInteger

            // Connection info
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_seen')->nullable();

            // Device and account info
            $table->json('device_info')->nullable();
            $table->string('push_name', 255)->nullable(); // Changed from 100 to 255
            $table->string('platform', 50)->nullable(); // Changed from enum to string(50)
            $table->boolean('is_business_account')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes matching Go struct requirements
            $table->index('user_id');
            $table->index('status');
            $table->index('is_active');
            $table->index('phone_number');
            $table->index('last_seen');
            $table->unique(['user_id', 'session_name']); // idx_user_session from Go
            $table->index(['user_id', 'is_active', 'status']); // For active sessions query
            $table->index(['status', 'last_seen']); // For cleanup queries
        });

        // WhatsApp Groups table (create BEFORE contacts since contacts references groups)
        Schema::create('whatsapp_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->char('session_id', 36)->nullable();
            $table->string('group_jid', 255); // Changed from 100 to 255
            $table->string('group_name', 255)->nullable(); // Added size 255
            $table->text('group_subject')->nullable(); // Group description
            $table->string('group_owner_jid', 100)->nullable();

            // Group settings
            $table->unsignedInteger('participant_count')->default(0);
            $table->unsignedInteger('admin_count')->default(0);
            $table->boolean('is_announcement')->default(false); // Broadcast group
            $table->boolean('is_locked')->default(false); // Admin only can send
            $table->boolean('is_active')->default(true);
            $table->boolean('is_member')->default(false); // Is the session a member
            $table->boolean('is_admin')->default(false); // Is the session an admin

            // Group metadata
            $table->timestamp('created_time')->nullable(); // Group creation time
            $table->timestamp('joined_at')->nullable(); // When session joined
            $table->timestamp('left_at')->nullable(); // When session left
            $table->json('group_settings')->nullable(); // Additional settings
            $table->string('invite_code', 50)->nullable();
            $table->text('group_picture_url')->nullable();

            // Stats
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();

            // Indexes matching Go struct requirements
            $table->index('user_id');
            $table->index('session_id');
            $table->index('group_jid');
            $table->index('is_active');
            $table->index('is_member');
            $table->index('participant_count');
            $table->index('last_activity_at');
            $table->unique(['user_id', 'group_jid']); // idx_user_group from Go
            $table->index(['user_id', 'is_active', 'is_member']); // Active groups query

            // Foreign key
            $table->foreign('session_id')->references('id')->on('whatsapp_sessions')->nullOnDelete();
        });

        // WhatsApp Contacts table (create AFTER groups since it references groups)
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->char('session_id', 36)->nullable();
            $table->string('jid', 255); // Changed from 100 to 255

            // Name fields
            $table->string('full_name', 255)->nullable(); // Added size 255
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 155)->nullable(); // Changed from 100 to 155
            $table->string('display_name', 100)->nullable(); // WhatsApp push name

            // Phone details
            $table->string('country_code', 10)->nullable();
            $table->string('mobile_number', 50)->nullable();

            // Contact type and status
            $table->enum('contact_type', ['individual', 'business', 'group_member', 'unknown'])->default('individual');
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_whatsapp_user')->default(true);
            $table->boolean('is_synced')->default(false);

            // Group relationship (contact can be from a group)
            $table->foreignId('group_id')->nullable()->constrained('whatsapp_groups')->nullOnDelete();
            $table->boolean('is_group_admin')->default(false);
            $table->boolean('is_group_member')->default(false); // NEW FIELD from Go struct

            // Business account fields
            $table->string('business_name')->nullable();
            $table->string('business_category')->nullable();

            // Additional metadata
            $table->string('profile_picture_url')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->unsignedInteger('message_count')->default(0);

            $table->timestamps();

            // Indexes matching Go struct requirements
            $table->index('user_id');
            $table->index('session_id');
            $table->index('jid');
            $table->index('mobile_number');
            $table->index('country_code');
            $table->index('group_id');
            $table->index('contact_type');
            $table->index('last_interaction_at');
            $table->unique(['user_id', 'jid']); // idx_user_jid from Go
            $table->index(['user_id', 'mobile_number']); // Phone lookup
            $table->index(['user_id', 'contact_type', 'is_blocked']); // Filtered queries

            // Foreign key to sessions
            $table->foreign('session_id')->references('id')->on('whatsapp_sessions')->nullOnDelete();
        });

        // WhatsApp Events table (for logging - consider partitioning for very large datasets)
        Schema::create('whatsapp_events', function (Blueprint $table) {
            $table->id();
            $table->char('session_id', 36)->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Event details
            $table->string('event_type');

            // Event data
            $table->json('event_data')->nullable();
            $table->string('related_jid', 100)->nullable(); // Related contact/group JID
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();

            // Request tracking
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('created_at')->nullable();

            // Indexes for performance (events table gets very large)
            $table->index('session_id');
            $table->index('user_id');
            $table->index('event_type');
            $table->index('related_jid');
            $table->index('created_at'); // For time-based queries
            $table->index(['user_id', 'event_type', 'created_at']); // Common query pattern
            $table->index(['session_id', 'event_type', 'created_at']); // Session events

            // Foreign key
            $table->foreign('session_id')->references('id')->on('whatsapp_sessions')->nullOnDelete();
        });

        // WhatsApp Messages table (optional - for message history)
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->char('session_id', 36);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('message_id', 100)->unique(); // WhatsApp message ID

            // Message participants
            $table->string('from_jid', 100);
            $table->string('to_jid', 100);
            $table->boolean('is_outgoing')->default(false);

            // Message content
            $table->enum('message_type', [
                'text',
                'image',
                'video',
                'audio',
                'document',
                'location',
                'contact',
                'sticker',
                'reaction',
                'deleted'
            ])->default('text');
            $table->text('content')->nullable();
            $table->json('media_data')->nullable(); // URL, mime type, size, etc.

            // Message status
            $table->string('status');

            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_forwarded')->default(false);

            // Group message
            $table->foreignId('group_id')->nullable()->constrained('whatsapp_groups')->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index('session_id');
            $table->index('user_id');
            $table->index('message_id');
            $table->index('from_jid');
            $table->index('to_jid');
            $table->index('message_type');
            $table->index('status');
            $table->index('sent_at');
            $table->index(['user_id', 'sent_at']); // User messages by time
            $table->index(['session_id', 'sent_at']); // Session messages
            $table->index(['from_jid', 'to_jid', 'sent_at']); // Conversation view

            // Foreign key
            $table->foreign('session_id')->references('id')->on('whatsapp_sessions')->onDelete('cascade');
        });

        // WhatsApp Rate Limits table (for managing API limits)
        Schema::create('whatsapp_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->char('session_id', 36)->nullable();

            $table->string('action_type');

            $table->unsignedInteger('hourly_count')->default(0);
            $table->unsignedInteger('daily_count')->default(0);
            $table->timestamp('hour_reset_at');
            $table->timestamp('day_reset_at');

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'action_type']);
            $table->index(['session_id', 'action_type']);
            $table->unique(['user_id', 'session_id', 'action_type']);

            // Foreign key
            $table->foreign('session_id')->references('id')->on('whatsapp_sessions')->nullOnDelete();
        });

        // Create partitioning for events table if using MySQL 8.0+ (for very large datasets)
        try {
            if (DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION) >= '8.0') {
                DB::statement('ALTER TABLE whatsapp_events PARTITION BY RANGE (YEAR(created_at)) (
                    PARTITION p2024 VALUES LESS THAN (2025),
                    PARTITION p2025 VALUES LESS THAN (2026),
                    PARTITION p2026 VALUES LESS THAN (2027),
                    PARTITION p_future VALUES LESS THAN MAXVALUE
                )');
            }
        } catch (\Exception $e) {
            // Partitioning is optional, log the error but don't fail migration
            \Log::info('Partitioning not applied to whatsapp_events table: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_rate_limits');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_events');
        Schema::dropIfExists('whatsapp_contacts');
        Schema::dropIfExists('whatsapp_groups');
        Schema::dropIfExists('whatsapp_sessions');
    }
};
