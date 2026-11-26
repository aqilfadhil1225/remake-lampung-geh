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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt');
            $table->longText('content');
            $table->enum('type', ['standard', 'live_report'])->default('standard');
            $table->enum('live_status', ['live', 'ended'])->nullable();
            $table->enum('region_scope', ['bandar_lampung', 'lampung', 'nasional'])->default('bandar_lampung');
            $table->enum('status', ['draft', 'in_review', 'revision_needed', 'scheduled', 'published', 'archived'])->default('draft');
            $table->text('editor_note')->nullable();
            $table->boolean('is_headline')->default(false);
            $table->boolean('is_breaking')->default(false);
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_sponsored')->default(false);
            $table->string('sponsor_name')->nullable();
            $table->string('featured_image_path')->nullable();
            $table->string('image_caption')->nullable();
            $table->string('image_credit')->nullable();
            $table->string('video_url')->nullable();
            $table->unsignedTinyInteger('reading_time_minutes')->default(1);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->fullText(['title', 'excerpt', 'content']);
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['status', 'is_headline', 'published_at']);
            $table->index(['type', 'live_status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
