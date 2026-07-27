<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $createdOrderItems = ! Schema::hasTable('order_items');

        if ($createdOrderItems) {
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

        if (! Schema::hasColumn('jerseys', 'sku')) {
            Schema::table('jerseys', function (Blueprint $table): void {
                $table->string('sku', 64)->nullable()->after('league_id');
            });

            DB::table('jerseys')->orderBy('id')->chunkById(200, function ($jerseys): void {
                foreach ($jerseys as $jersey) {
                    DB::table('jerseys')->where('id', $jersey->id)->update([
                        'sku' => 'SA-JER-'.str_pad((string) $jersey->id, 6, '0', STR_PAD_LEFT),
                    ]);
                }
            });

            Schema::table('jerseys', function (Blueprint $table): void {
                $table->unique('sku');
            });
        }

        if (! Schema::hasColumn('leagues', 'deleted_at')) {
            Schema::table('leagues', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('jersey_order')) {
            DB::table('jersey_order')
                ->leftJoin('jerseys', 'jerseys.id', '=', 'jersey_order.jersey_id')
                ->select([
                    'jersey_order.*',
                    'jerseys.name as product_name',
                    'jerseys.slug as product_slug',
                    'jerseys.image as image_path',
                    'jerseys.weight as product_weight',
                    'jerseys.price as unit_price',
                    'jerseys.price_nameset as nameset_unit_price',
                    'jerseys.sku as product_sku',
                ])
                ->orderBy('jersey_order.id')
                ->chunk(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $decodedNameset = null;

                        if (is_string($row->nameset) && trim($row->nameset) !== '') {
                            $candidate = json_decode($row->nameset, true);
                            $decodedNameset = is_array($candidate) ? $candidate : null;
                        }

                        DB::table('order_items')->updateOrInsert(
                            ['id' => $row->id],
                            [
                                'order_id' => $row->order_id,
                                'jersey_id' => $row->jersey_id,
                                'product_name' => $row->product_name ?: 'Archived product',
                                'product_sku' => $row->product_sku ?: 'ARCHIVED-'.Str::upper(Str::random(12)),
                                'product_slug' => $row->product_slug,
                                'image_path' => $row->image_path,
                                'product_weight' => (int) ($row->product_weight ?? 0),
                                'size' => $row->size,
                                'quantity' => $row->quantity,
                                'unit_price' => (int) ($row->unit_price ?? 0),
                                'nameset_unit_price' => (int) ($row->nameset_unit_price ?? 0),
                                'line_total' => $row->total_price,
                                'nameset' => $decodedNameset === null ? null : json_encode($decodedNameset, JSON_THROW_ON_ERROR),
                                'created_at' => $row->created_at,
                                'updated_at' => $row->updated_at,
                            ],
                        );
                    }
                });

            Schema::drop('jersey_order');
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            Schema::table('jerseys', function (Blueprint $table): void {
                $table->dropForeign(['league_id']);
                $table->foreign('league_id')->references('id')->on('leagues')->restrictOnDelete();
            });

            if (! $createdOrderItems) {
                Schema::table('order_items', function (Blueprint $table): void {
                    $table->dropForeign(['order_id']);
                    $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // Order-item snapshots are intentionally not converted back to a destructive pivot table.
    }
};
