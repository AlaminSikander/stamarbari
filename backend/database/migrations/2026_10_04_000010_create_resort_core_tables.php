<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general')->index();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone');
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['phone', 'email']);
        });

        Schema::create('room_amenities', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_bn')->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('room_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_bn')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_bn')->nullable();
            $table->unsignedTinyInteger('max_adults')->default(2);
            $table->unsignedTinyInteger('max_children')->default(2);
            $table->string('view_en')->nullable();
            $table->string('view_bn')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('tour_360_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_category_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('extra_bed_allowed')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['room_category_id', 'code']);
        });

        Schema::create('room_amenity_room_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_amenity_id')->constrained()->cascadeOnDelete();
            $table->unique(['room_category_id', 'room_amenity_id'], 'room_cat_amenity_unique');
        });

        Schema::create('room_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_category_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_en')->nullable();
            $table->string('alt_bn')->nullable();
            $table->string('caption_en')->nullable();
            $table->string('caption_bn')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('room_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_category_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('base');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('BDT');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedTinyInteger('min_nights')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['room_category_id', 'type', 'is_active']);
        });

        Schema::create('seasonal_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('kind')->default('seasonal');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('amount', 12, 2);
            $table->unsignedSmallInteger('priority')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('room_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['room_id', 'starts_on', 'ends_on']);
        });

        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_bn')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_bn')->nullable();
            $table->decimal('price_per_adult_per_night', 12, 2)->default(0);
            $table->decimal('price_per_child_per_night', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_bn')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_bn')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('pricing_unit')->default('per_booking');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pickup')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addons');
        Schema::dropIfExists('meal_plans');
        Schema::dropIfExists('room_blocks');
        Schema::dropIfExists('seasonal_prices');
        Schema::dropIfExists('room_prices');
        Schema::dropIfExists('room_images');
        Schema::dropIfExists('room_amenity_room_category');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_categories');
        Schema::dropIfExists('room_amenities');
        Schema::dropIfExists('guests');
        Schema::dropIfExists('settings');
    }
};
