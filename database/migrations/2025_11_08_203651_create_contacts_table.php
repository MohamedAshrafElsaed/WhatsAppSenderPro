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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('set null');
            $table->string('whatsapp_jid')->nullable();
            $table->boolean('is_whatsapp_valid')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->enum('source', ['manual', 'csv_import', 'excel_import', 'api'])->default('manual');
            $table->foreignId('import_id')->nullable()->constrained('contact_imports')->onDelete('set null');
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('phone_number');
            $table->index('email');
            $table->index('is_whatsapp_valid');
            $table->index('source');
            $table->unique(['user_id', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
