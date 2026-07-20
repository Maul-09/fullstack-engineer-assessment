<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_product_uses_flash_price_only_during_its_period(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'FLASH-001')->firstOrFail();

        $this->assertSame(100000, $product->regular_price);
        $this->assertSame(10000, $product->flash_price);
        $this->assertSame(10, $product->inventory_quantity);
        $this->assertSame(100000, $product->currentPrice($product->flash_starts_at->subSecond()));
        $this->assertSame(10000, $product->currentPrice($product->flash_starts_at));
        $this->assertSame(100000, $product->currentPrice($product->flash_ends_at));
    }

    public function test_order_relations_are_connected(): void
    {
        $this->seed();

        $product = Product::query()->where('sku', 'FLASH-001')->firstOrFail();
        $order = Order::query()->create(['total_amount' => 20000]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => $product->currentPrice(now()),
        ]);

        $this->assertTrue($order->items()->firstOrFail()->is($item));
        $this->assertTrue($item->order->is($order));
        $this->assertTrue($item->product->is($product));
        $this->assertTrue($product->orderItems()->firstOrFail()->is($item));
    }
}
