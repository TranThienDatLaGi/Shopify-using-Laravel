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
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('based_on', ['current_price', 'compare_at_price'])->default('current_price');
            $table->decimal('discount_value', 10, 2);
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent');
            $table->enum('applies_to', ['products', 'tags', 'vendors', 'collections', 'whole_store']);
            $table->json('applies_to_value')->nullable();
            $table->enum('status', ['active', 'inactive','archived'])->default('active');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->foreignId('shop_id')->constrained('users')->onDelete('cascade');
            $table->string('add_tag')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
