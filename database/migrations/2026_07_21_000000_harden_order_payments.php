<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE orders MODIFY status VARCHAR(30) NOT NULL');
            DB::statement("UPDATE orders SET courier_services = NULL WHERE courier_services = '' OR JSON_VALID(courier_services) = 0");
            DB::statement('ALTER TABLE orders MODIFY courier_services JSON NULL');
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('subtotal')->default(0)->after('invoice_number');
            $table->unsignedBigInteger('shipping_amount')->default(0)->after('subtotal');
            $table->unsignedBigInteger('tax_amount')->default(0)->after('shipping_amount');
            $table->unsignedBigInteger('discount_amount')->default(0)->after('tax_amount');
            $table->string('provider_transaction_id', 100)->nullable()->unique()->after('status');
            $table->string('transaction_status', 50)->nullable()->index()->after('provider_transaction_id');
            $table->string('payment_type', 100)->nullable()->after('transaction_status');
            $table->string('status_code', 10)->nullable()->after('payment_type');
            $table->string('fraud_status', 30)->nullable()->after('status_code');
            $table->timestamp('reservation_expires_at')->nullable()->index()->after('fraud_status');
            $table->timestamp('processed_at')->nullable()->index()->after('payment_type');
            $table->timestamp('paid_at')->nullable()->index()->after('processed_at');
        });

        DB::table('orders')
            ->select(['id', 'total_price'])
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    $subtotal = match (true) {
                        Schema::hasTable('order_items') => (int) DB::table('order_items')
                            ->where('order_id', $order->id)
                            ->sum('line_total'),
                        Schema::hasTable('jersey_order') => (int) DB::table('jersey_order')
                            ->where('order_id', $order->id)
                            ->sum('total_price'),
                        default => 0,
                    };
                    $subtotal = $subtotal > 0 ? $subtotal : (int) $order->total_price;

                    DB::table('orders')->where('id', $order->id)->update([
                        'subtotal' => $subtotal,
                        'shipping_amount' => max(0, (int) $order->total_price - $subtotal),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['provider_transaction_id']);
            $table->dropIndex(['transaction_status']);
            $table->dropIndex(['reservation_expires_at']);
            $table->dropIndex(['processed_at']);
            $table->dropIndex(['paid_at']);
            $table->dropColumn([
                'subtotal',
                'shipping_amount',
                'tax_amount',
                'discount_amount',
                'provider_transaction_id',
                'transaction_status',
                'payment_type',
                'status_code',
                'fraud_status',
                'reservation_expires_at',
                'processed_at',
                'paid_at',
            ]);
        });
    }
};
