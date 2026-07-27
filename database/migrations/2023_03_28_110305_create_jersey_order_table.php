<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('jersey_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 150);
            $table->string('product_sku', 64);
            $table->string('product_slug', 255)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->unsignedBigInteger('product_weight')->default(0);
            $table->string('size', 10);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('nameset_unit_price')->default(0);
            $table->unsignedBigInteger('line_total');
            $table->json('nameset')->nullable();
            $table->timestamp('stock_reserved_at')->nullable()->index();
            $table->timestamp('stock_released_at')->nullable()->index();
            $table->timestamp('stock_consumed_at')->nullable()->index();
            $table->timestamps();
            $table->index(['order_id', 'jersey_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
