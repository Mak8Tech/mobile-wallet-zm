<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('mobile_wallet.database.table', 'mobile_wallet_transactions'), function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_id')->unique();
            $table->string('provider')->index(); // mtn, airtel, zamtel
            $table->string('provider_transaction_id')->nullable()->index();
            $table->string('phone_number')->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ZMW');
            $table->string('status')->default('pending')->index();
            $table->string('message')->nullable();
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('reference')->nullable()->index();
            $table->string('narration')->nullable();
            
            // Use explicit columns with a custom index name instead of morphs()
            $table->unsignedBigInteger('transactionable_id');
            $table->string('transactionable_type');
            $table->index(['transactionable_type', 'transactionable_id'], 'trx_morph_idx');
            
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Add a composite index for common queries
            $table->index(['provider', 'status', 'created_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('mobile_wallet.database.table', 'mobile_wallet_transactions'));
    }
};