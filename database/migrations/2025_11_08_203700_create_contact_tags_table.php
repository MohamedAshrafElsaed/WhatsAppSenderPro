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
        Schema::create('contact_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('color')->default('#25D366');
            $table->integer('contacts_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->unique(['user_id', 'name']);
        });

        Schema::create('contact_tag', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->foreignId('contact_tag_id')->constrained('contact_tags')->onDelete('cascade');
            $table->timestamps();

            // Primary key
            $table->primary(['contact_id', 'contact_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_tag');
        Schema::dropIfExists('contact_tags');
    }
};
