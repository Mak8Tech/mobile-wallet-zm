<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mak8Tech\MobileWalletZm\Models\WalletTransaction;
use Mak8Tech\MobileWalletZm\Tests\TestCase;

class WalletTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected WalletTransaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test transaction
        $this->transaction = WalletTransaction::create([
            'provider' => 'mtn',
            'phone_number' => '0977123456',
            'amount' => 100.00,
            'currency' => 'ZMW',
            'reference' => 'REFERENCE123',
            'narration' => 'Test payment',
            'status' => 'pending',
        ]);
    }

    public function test_it_sets_table_name_from_config(): void
    {
        config(['mobile_wallet.database.table' => 'custom_transactions_table']);
        
        $transaction = new WalletTransaction();
        $this->assertEquals('custom_transactions_table', $transaction->getTable());
    }

    public function test_it_sets_connection_from_config(): void
    {
        config(['mobile_wallet.database.connection' => 'mysql_test']);
        
        $transaction = new WalletTransaction();
        $this->assertEquals('mysql_test', $transaction->getConnectionName());
    }

    public function test_it_generates_transaction_id_automatically(): void
    {
        $this->assertNotNull($this->transaction->transaction_id);
        $this->assertIsString($this->transaction->transaction_id);
        $this->assertEquals(36, strlen($this->transaction->transaction_id)); // UUID length
    }

    public function test_it_marks_transaction_as_paid(): void
    {
        $this->transaction->markAsPaid('provider-transaction-123');
        
        $this->assertEquals('paid', $this->transaction->status);
        $this->assertNotNull($this->transaction->paid_at);
        $this->assertEquals('provider-transaction-123', $this->transaction->provider_transaction_id);
    }

    public function test_it_marks_transaction_as_failed(): void
    {
        $this->transaction->markAsFailed('Payment failed due to insufficient funds');
        
        $this->assertEquals('failed', $this->transaction->status);
        $this->assertNotNull($this->transaction->failed_at);
        $this->assertEquals('Payment failed due to insufficient funds', $this->transaction->message);
    }

    public function test_it_checks_if_transaction_is_successful(): void
    {
        $this->assertFalse($this->transaction->isSuccessful());
        
        $this->transaction->update(['status' => 'paid']);
        $this->assertTrue($this->transaction->isSuccessful());
    }

    public function test_it_checks_if_transaction_has_failed(): void
    {
        $this->assertFalse($this->transaction->hasFailed());
        
        $this->transaction->update(['status' => 'failed']);
        $this->assertTrue($this->transaction->hasFailed());
    }

    public function test_it_checks_if_transaction_is_pending(): void
    {
        $this->assertTrue($this->transaction->isPending());
        
        $this->transaction->update(['status' => 'paid']);
        $this->assertFalse($this->transaction->isPending());
    }

    public function test_it_can_be_associated_with_a_morphable_model(): void
    {
        // Create a mock morphable model
        $morphable = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'orders';
            protected $guarded = [];
        };
        
        // Create test table for the morphable model
        $this->app['db']->connection()->getSchemaBuilder()->create('orders', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        
        // Create a record
        $morphable = $morphable->create(['name' => 'Test Order']);
        
        // Associate the transaction
        $transaction = WalletTransaction::create([
            'provider' => 'mtn',
            'phone_number' => '0977123456',
            'amount' => 100.00,
            'currency' => 'ZMW',
            'reference' => 'ORDER-123',
            'narration' => 'Order payment',
            'status' => 'pending',
            'transactionable_type' => get_class($morphable),
            'transactionable_id' => $morphable->id,
        ]);
        
        $this->assertEquals($morphable->id, $transaction->transactionable->id);
        $this->assertEquals('Test Order', $transaction->transactionable->name);
    }

    public function test_it_casts_attributes_correctly(): void
    {
        $transaction = WalletTransaction::create([
            'provider' => 'mtn',
            'phone_number' => '0977123456',
            'amount' => 100,
            'currency' => 'ZMW',
            'reference' => 'REFERENCE123',
            'narration' => 'Test payment',
            'status' => 'pending',
            'raw_request' => ['amount' => 100, 'currency' => 'ZMW'],
            'raw_response' => ['status' => 'success'],
        ]);
        
        $this->assertIsArray($transaction->raw_request);
        $this->assertIsArray($transaction->raw_response);
        $this->assertIsFloat($transaction->amount);
    }
} 