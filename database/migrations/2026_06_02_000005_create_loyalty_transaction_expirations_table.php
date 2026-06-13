<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_transaction_expirations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('earned_transaction_id')->constrained('loyalty_transactions')->cascadeOnDelete();
            $table->foreignId('expired_transaction_id')->constrained('loyalty_transactions')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('earned_transaction_id', 'lte_earned_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transaction_expirations');
    }
};
