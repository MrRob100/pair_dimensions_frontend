<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pair_inputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pair_id');
            $table->enum('type', ['input', 'shave']);
            $table->string('symbol', 20);
            $table->decimal('amount', 20, 8);
            $table->decimal('amount_usd', 20, 8);
            $table->decimal('price', 20, 8);
            $table->datetime('created_at');

            $table->index('pair_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pair_inputs');
    }
};
