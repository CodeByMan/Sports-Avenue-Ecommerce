<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Jersey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** @param array<string, mixed> $data */
    public function createUser(array $data = [], int $count = 1): User|Collection
    {
        $users = User::factory()->count($count)->create($data);

        return $count < 2 ? $users->firstOrFail() : $users;
    }

    /** @param array<string, mixed> $data */
    public function authenticatedUser(array $data = []): User
    {
        /** @var User $user */
        $user = $this->createUser($data);
        $this->actingAs($user);

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    protected function createOrderItem(Order $order, Jersey $jersey, array $overrides = []): OrderItem
    {
        $quantity = (int) ($overrides['quantity'] ?? 1);
        $namesets = $overrides['nameset'] ?? [];
        $namesetCount = is_array($namesets) ? count($namesets) : 0;
        $unitPrice = (int) ($overrides['unit_price'] ?? $jersey->price);
        $namesetUnitPrice = (int) ($overrides['nameset_unit_price'] ?? $jersey->price_nameset);

        return $order->items()->create(array_merge([
            'jersey_id' => $jersey->getKey(),
            'product_name' => $jersey->name,
            'product_sku' => $jersey->sku,
            'product_slug' => $jersey->slug,
            'image_path' => $jersey->image,
            'product_weight' => $jersey->weight,
            'size' => 'M',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'nameset_unit_price' => $namesetUnitPrice,
            'line_total' => ($unitPrice * $quantity) + ($namesetUnitPrice * $namesetCount),
            'nameset' => $namesets,
        ], $overrides));
    }

    protected function deleteFile(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}
