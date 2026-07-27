<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $userColumns = [
            'deleted_at' => Schema::hasColumn('users', 'deleted_at'),
            'suspended_at' => Schema::hasColumn('users', 'suspended_at'),
            'blocked_at' => Schema::hasColumn('users', 'blocked_at'),
            'status_changed_at' => Schema::hasColumn('users', 'status_changed_at'),
            'status_reason' => Schema::hasColumn('users', 'status_reason'),
        ];

        Schema::table('users', function (Blueprint $table) use ($userColumns): void {
            if (! $userColumns['deleted_at']) {
                $table->softDeletes();
            }

            if (! $userColumns['suspended_at']) {
                $table->timestamp('suspended_at')->nullable()->index();
            }

            if (! $userColumns['blocked_at']) {
                $table->timestamp('blocked_at')->nullable()->index();
            }

            if (! $userColumns['status_changed_at']) {
                $table->timestamp('status_changed_at')->nullable()->index();
            }

            if (! $userColumns['status_reason']) {
                $table->text('status_reason')->nullable();
            }
        });

        $orderColumns = [
            'fulfillment_status' => Schema::hasColumn('orders', 'fulfillment_status'),
            'fulfillment_status_updated_at' => Schema::hasColumn('orders', 'fulfillment_status_updated_at'),
        ];

        Schema::table('orders', function (Blueprint $table) use ($orderColumns): void {
            if (! $orderColumns['fulfillment_status']) {
                $table->string('fulfillment_status', 40)
                    ->default(Order::FULFILLMENT_PENDING)
                    ->index()
                    ->after('status');
            }

            if (! $orderColumns['fulfillment_status_updated_at']) {
                $table->timestamp('fulfillment_status_updated_at')
                    ->nullable()
                    ->index()
                    ->after('fulfillment_status');
            }
        });

        if (! Schema::hasTable('order_status_histories')) {
            Schema::create('order_status_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40);
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'created_at']);
            });
        }

        DB::table('orders')
            ->where('status', '!=', Order::STATUS_IN_CART)
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    $fulfillmentStatus = match ((string) $order->status) {
                        Order::STATUS_SUCCESS => Order::FULFILLMENT_CONFIRMED,
                        Order::STATUS_FAILED => Order::FULFILLMENT_CANCELLED,
                        default => Order::FULFILLMENT_PENDING,
                    };

                    DB::table('orders')->where('id', $order->id)->update([
                        'fulfillment_status' => $fulfillmentStatus,
                        'fulfillment_status_updated_at' => $order->updated_at ?? now(),
                    ]);

                    DB::table('order_status_histories')->updateOrInsert(
                        [
                            'order_id' => $order->id,
                            'from_status' => null,
                        ],
                        [
                            'to_status' => $fulfillmentStatus,
                            'changed_by' => null,
                            'note' => 'Initial workflow status created during migration.',
                            'created_at' => $order->created_at ?? now(),
                            'updated_at' => $order->updated_at ?? now(),
                        ],
                    );
                }
            });

        DB::table('users')
            ->whereNotIn('status', User::$status)
            ->update([
                'status' => User::STATUS_INACTIVE,
                'status_changed_at' => now(),
            ]);

        DB::table('users')
            ->where(fn ($query) => $query->whereNull('username')->orWhere('username', ''))
            ->orderBy('id')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    $base = 'account'.$user->id;
                    $username = $base;
                    $suffix = 1;

                    while (DB::table('users')
                        ->where('username', $username)
                        ->where('id', '!=', $user->id)
                        ->exists()) {
                        $username = $base.$suffix;
                        $suffix++;
                    }

                    DB::table('users')->where('id', $user->id)->update([
                        'username' => $username,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');

        $orderColumns = array_values(array_filter([
            Schema::hasColumn('orders', 'fulfillment_status') ? 'fulfillment_status' : null,
            Schema::hasColumn('orders', 'fulfillment_status_updated_at') ? 'fulfillment_status_updated_at' : null,
        ]));

        if ($orderColumns !== []) {
            Schema::table('orders', fn (Blueprint $table) => $table->dropColumn($orderColumns));
        }

        $userColumns = array_values(array_filter([
            Schema::hasColumn('users', 'deleted_at') ? 'deleted_at' : null,
            Schema::hasColumn('users', 'suspended_at') ? 'suspended_at' : null,
            Schema::hasColumn('users', 'blocked_at') ? 'blocked_at' : null,
            Schema::hasColumn('users', 'status_changed_at') ? 'status_changed_at' : null,
            Schema::hasColumn('users', 'status_reason') ? 'status_reason' : null,
        ]));

        if ($userColumns !== []) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($userColumns));
        }
    }
};
