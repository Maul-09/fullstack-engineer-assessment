<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->integer('quantity');
            $table->bigInteger('unit_price');
            $table->timestampsTz();
            $table->unique(['order_id', 'product_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE order_items
                    ADD CONSTRAINT order_items_quantity_positive CHECK (quantity > 0),
                    ADD CONSTRAINT order_items_unit_price_non_negative CHECK (unit_price >= 0)
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
