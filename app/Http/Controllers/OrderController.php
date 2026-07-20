<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! $request->isJson()) {
            return $this->error('UNSUPPORTED_MEDIA_TYPE', 'Content-Type harus application/json.', 415);
        }

        if (! json_validate($request->getContent())) {
            return $this->error('INVALID_JSON', 'JSON tidak valid.', 400);
        }

        $validator = Validator::make($request->json()->all(), [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                'VALIDATION_ERROR',
                'Payload tidak valid.',
                422,
                $validator->errors()->toArray(),
            );
        }

        $items = collect($validator->validated()['items'])
            ->groupBy('product_id')
            ->map(fn ($rows, $productId) => [
                'product_id' => (int) $productId,
                'quantity' => (int) $rows->sum('quantity'),
            ])
            ->sortBy('product_id')
            ->values();

        $at = now();

        $order = DB::transaction(function () use ($items, $at): Order {
            $lines = [];

            foreach ($items as $item) {
                $affected = Product::query()
                    ->whereKey($item['product_id'])
                    ->where('inventory_quantity', '>=', $item['quantity'])
                    ->decrement('inventory_quantity', $item['quantity']);

                if ($affected !== 1) {
                    $exists = Product::query()->whereKey($item['product_id'])->exists();

                    throw new HttpResponseException($exists
                        ? $this->error('OUT_OF_STOCK', 'Stok produk tidak mencukupi.', 409, ['product_id' => $item['product_id']])
                        : $this->error('PRODUCT_NOT_FOUND', 'Produk tidak ditemukan.', 404, ['product_id' => $item['product_id']]));
                }

                $product = Product::query()->findOrFail($item['product_id']);
                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->currentPrice($at),
                ];
            }

            $order = Order::query()->create([
                'total_amount' => collect($lines)->sum(
                    fn (array $line) => $line['quantity'] * $line['unit_price'],
                ),
            ]);

            $order->items()->createMany($lines);

            return $order->load('items');
        }, attempts: 3);

        return response()->json([
            'data' => [
                'id' => $order->id,
                'items' => $order->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->quantity * $item->unit_price,
                ]),
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at->toISOString(),
            ],
        ], 201);
    }

    private function error(string $code, string $message, int $status, array $details = []): JsonResponse
    {
        $error = compact('code', 'message');

        if ($details !== []) {
            $error['details'] = $details;
        }

        return response()->json(['error' => $error], $status);
    }
}
