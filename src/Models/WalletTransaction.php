<?php

namespace Mak8Tech\MobileWalletZm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class WalletTransaction extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'raw_request' => 'array',
        'raw_response' => 'array',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('mobile_wallet.database.table', 'mobile_wallet_transactions'));
        $this->setConnection(config('mobile_wallet.database.connection', config('database.default')));
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate a transaction_id if one wasn't set
            if (empty($model->transaction_id)) {
                $model->transaction_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the parent transactionable model.
     */
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the transaction as paid.
     */
    public function markAsPaid(string $providerTransactionId = null): self
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'provider_transaction_id' => $providerTransactionId ?? $this->provider_transaction_id,
        ]);

        return $this;
    }

    /**
     * Mark the transaction as failed.
     */
    public function markAsFailed(string $message = null): self
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'message' => $message ?? $this->message,
        ]);

        return $this;
    }

    /**
     * Check if the transaction was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if the transaction has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
