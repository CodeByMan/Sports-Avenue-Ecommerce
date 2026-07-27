<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jerseys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('league_id')->constrained()->restrictOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('name', 150);
            $table->string('slug', 255)->unique();
            $table->string('type', 150);
            $table->unsignedBigInteger('weight');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('price_nameset');
            $table->string('image', 255);
            $table->json('stock');
            $table->unsignedBigInteger('sold')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jerseys');
    }
};
