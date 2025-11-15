<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Basic, Standard, Premium
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2); // Monthly price
            $table->integer('max_profiles')->default(1);
            $table->integer('max_concurrent_streams')->default(1);
            $table->boolean('hd_support')->default(false);
            $table->boolean('uhd_support')->default(false);
            $table->boolean('offline_download')->default(false);
            $table->boolean('ad_free')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
