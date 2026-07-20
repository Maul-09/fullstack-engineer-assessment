<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_endpoint_returns_current_price_and_stock(): void
    {
        $this->seed();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'FLASH-001')
            ->assertJsonPath('data.0.current_price', 10000)
            ->assertJsonPath('data.0.is_flash_sale_active', true)
            ->assertJsonPath('data.0.inventory_quantity', 10);
    }

    public function test_order_is_created_with_price_snapshot_and_stock_decrement(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'FLASH-001')->firstOrFail();

        $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.total_amount', 20000)
            ->assertJsonPath('data.items.0.product_name', 'Wireless Mechanical Keyboard K87')
            ->assertJsonPath('data.items.0.unit_price', 10000)
            ->assertJsonPath('data.items.0.subtotal', 20000);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'inventory_quantity' => 8,
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10000,
        ]);
    }

    public function test_duplicate_products_are_combined(): void
    {
        $this->seed();
        $product = Product::query()->where('sku', 'FLASH-001')->firstOrFail();

        $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'inventory_quantity' => 7,
        ]);
    }

    public function test_invalid_payload_returns_validation_error(): void
    {
        $this->postJson('/api/orders', ['items' => []])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    public function test_invalid_json_and_content_type_are_rejected(): void
    {
        $this->call(
            'POST',
            '/api/orders',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: '{"items":',
        )->assertStatus(400)->assertJsonPath('error.code', 'INVALID_JSON');

        $this->call(
            'POST',
            '/api/orders',
            server: ['CONTENT_TYPE' => 'text/plain', 'HTTP_ACCEPT' => 'application/json'],
            content: '{"items":[]}',
        )->assertStatus(415)->assertJsonPath('error.code', 'UNSUPPORTED_MEDIA_TYPE');
    }

    public function test_unknown_product_returns_not_found(): void
    {
        $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => 999, 'quantity' => 1],
            ],
        ])->assertNotFound()
            ->assertJsonPath('error.code', 'PRODUCT_NOT_FOUND')
            ->assertJsonPath('error.details.product_id', 999);
    }

    public function test_out_of_stock_rolls_back_the_entire_order(): void
    {
        $this->seed();
        $first = Product::query()->where('sku', 'FLASH-001')->firstOrFail();
        $second = Product::query()->create([
            'sku' => 'FLASH-002',
            'name' => 'Wireless Mouse M331',
            'regular_price' => 50000,
            'inventory_quantity' => 1,
        ]);

        $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $first->id, 'quantity' => 1],
                ['product_id' => $second->id, 'quantity' => 2],
            ],
        ])->assertConflict()
            ->assertJsonPath('error.code', 'OUT_OF_STOCK')
            ->assertJsonPath('error.details.product_id', $second->id);

        $this->assertDatabaseHas('products', [
            'id' => $first->id,
            'inventory_quantity' => 10,
        ]);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }
}
