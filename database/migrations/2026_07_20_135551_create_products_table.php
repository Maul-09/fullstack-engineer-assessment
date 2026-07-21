<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->bigInteger('regular_price');
            $table->bigInteger('flash_price')->nullable();
            $table->timestampTz('flash_starts_at')->nullable();
            $table->timestampTz('flash_ends_at')->nullable();
            $table->integer('inventory_quantity')->default(0);
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE products
                    ADD CONSTRAINT products_regular_price_non_negative CHECK (regular_price >= 0),
                    ADD CONSTRAINT products_flash_price_valid CHECK (flash_price IS NULL OR (flash_price >= 0 AND flash_price < regular_price)),
                    ADD CONSTRAINT products_flash_period_valid CHECK (
                        (flash_starts_at IS NULL AND flash_ends_at IS NULL)
                        OR
                        (flash_starts_at IS NOT NULL AND flash_ends_at IS NOT NULL AND flash_starts_at < flash_ends_at)
                    ),
                    ADD CONSTRAINT products_inventory_non_negative CHECK (inventory_quantity >= 0)
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
