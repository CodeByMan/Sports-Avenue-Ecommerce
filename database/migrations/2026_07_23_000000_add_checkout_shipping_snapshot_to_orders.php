<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('customer_name', 120)->nullable()->after('user_id');
            $table->string('customer_email', 190)->nullable()->after('customer_name');
            $table->string('shipping_phone', 20)->nullable()->after('courier_services');
            $table->text('shipping_address')->nullable()->after('shipping_phone');
            $table->string('shipping_province', 120)->nullable()->after('shipping_address');
            $table->string('shipping_city', 120)->nullable()->after('shipping_province');
            $table->string('shipping_postal_code', 10)->nullable()->after('shipping_city');
            $table->string('payment_method', 40)->nullable()->after('payment_type');
            $table->timestamp('placed_at')->nullable()->index()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['placed_at']);
            $table->dropColumn([
                'customer_name',
                'customer_email',
                'shipping_phone',
                'shipping_address',
                'shipping_province',
                'shipping_city',
                'shipping_postal_code',
                'payment_method',
                'placed_at',
            ]);
        });
    }
};
