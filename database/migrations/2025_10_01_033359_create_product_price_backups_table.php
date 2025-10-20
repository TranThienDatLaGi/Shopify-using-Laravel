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
        Schema::create('product_price_backups', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name');
            $table->string('product_id');
            $table->string('variant_id');
            $table->decimal('original_price', 10, 2)->nullable();
            $table->decimal('original_compare_at_price', 10, 2)->nullable();
            $table->integer('rule_id')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_backups');
    }
};
