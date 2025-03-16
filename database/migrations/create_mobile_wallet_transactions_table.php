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
            $table->string('phone_number');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ZMW');
            $table->string('status')->default('pending')->index();
            $table->string('message')->nullable();
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('reference')->nullable();
            $table->string('narration')->nullable();
            $table->morphs('transactionable'); // Polymorphic relationship
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
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