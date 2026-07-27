<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Jersey;
use App\Models\League;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use DomainException;
use Tests\TestCase;

class CatalogHistoryProtectionTest extends TestCase
{
    private User $user;

    private League $league;

    private Jersey $jersey;

    private Order $paidOrder;

    private OrderItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->league = League::factory()->create(['created_by' => $this->user->id]);
        $this->jersey = Jersey::factory()->create([
            'league_id' => $this->league->id,
            'created_by' => $this->user->id,
            'name' => 'Historical Home Jersey',
            'sku' => 'SA-HISTORY-001',
            'price' => 450000,
        ]);
        $this->paidOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'subtotal' => 450000,
            'total_price' => 450000,
            'status' => Order::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);
        $this->item = $this->createOrderItem($this->paidOrder, $this->jersey);
    }

    public function test_deleting_an_unused_product_succeeds(): void
    {
        $unused = Jersey::factory()->create([
            'league_id' => $this->league->id,
        ]);

        $this->assertTrue((bool) $unused->delete());

        $this->assertDatabaseMissing('jerseys', [
            'id' => $unused->id,
        ]);
    }

    public function test_permanently_deleting_product_preserves_order_snapshot(): void
    {
        $this->jersey->delete();

        $this->assertDatabaseMissing('jerseys', [
            'id' => $this->jersey->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'id' => $this->item->id,
            'jersey_id' => null,
            'product_name' => 'Historical Home Jersey',
            'product_sku' => 'SA-HISTORY-001',
            'line_total' => 450000,
        ]);
    }

    public function test_deleting_a_league_with_products_is_rejected(): void
    {
        $this->expectException(DomainException::class);

        $this->expectExceptionMessage(
            'A league with products cannot be permanently deleted.'
        );

        $this->league->delete();
    }

    public function test_product_edits_do_not_rewrite_historical_order_information(): void
    {
        $this->jersey->update([
            'name' => 'Renamed Current Product',
            'sku' => 'SA-RENAMED-999',
            'price' => 999999,
            'image' => 'new-image.jpg',
        ]);

        $this->assertDatabaseHas('order_items', [
            'id' => $this->item->id,
            'product_name' => 'Historical Home Jersey',
            'product_sku' => 'SA-HISTORY-001',
            'unit_price' => 450000,
            'line_total' => 450000,
        ]);
    }

    public function test_historical_order_item_snapshot_is_immutable(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Historical order item snapshots are immutable.');

        $this->item->update(['product_name' => 'Tampered product']);
    }

    public function test_paid_order_cannot_be_deleted(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Historical orders cannot be deleted.');

        $this->paidOrder->delete();
    }
}
