<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->nullable()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['house', 'room', 'apartment']);
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('province', 40);
            $table->string('district', 60);
            $table->string('commune', 120)->nullable();
            $table->string('address', 255)->nullable();
            $table->decimal('length_m', 7, 2);
            $table->decimal('width_m', 7, 2);
            $table->unsignedSmallInteger('floors')->default(1);
            $table->unsignedSmallInteger('rooms')->default(1);
            $table->unsignedSmallInteger('bathrooms')->default(1);
            $table->json('amenities');
            $table->json('photos');
            $table->string('video_url')->nullable();
            $table->unsignedTinyInteger('tone')->default(0);
            $table->string('maps_url', 500)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone', 32);
            $table->string('telegram', 32)->nullable();
            $table->enum('status', ['available', 'rented'])->default('available');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('inquiries')->default(0);
            $table->timestamps();

            $table->index(['province', 'district']);
            $table->index(['status', 'price']);
            $table->index('type');
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['user_id', 'listing_id']);
        });

        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('reset_token_hash')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('listings');
    }
};
