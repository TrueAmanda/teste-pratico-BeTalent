<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('gateway_id')->constrained()->onDelete('cascade');
            $table->string('external_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'refunded'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('card_last_numbers', 4);
            $table->text('gateway_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
