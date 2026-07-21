<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('total_amount')->default(0);
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_amount_non_negative CHECK (total_amount >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
