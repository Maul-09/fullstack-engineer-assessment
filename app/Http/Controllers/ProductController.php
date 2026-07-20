<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $at = now();

        $products = Product::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'regular_price' => $product->regular_price,
                'current_price' => $product->currentPrice($at),
                'is_flash_sale_active' => $product->flash_price !== null
                    && $product->currentPrice($at) === $product->flash_price,
                'inventory_quantity' => $product->inventory_quantity,
            ]);

        return response()->json(['data' => $products]);
    }
}
