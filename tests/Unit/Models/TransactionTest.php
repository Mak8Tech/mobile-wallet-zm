<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Models;

use Illuminate\Support\Facades\Crypt;
use Mak8Tech\MobileWalletZm\Models\Transaction;
use Mak8Tech\MobileWalletZm\Tests\TestCase;

class TransactionTest extends TestCase
{
    /** @test */
    public function it_encrypts_sensitive_data_when_setting_attributes()
    {
        // Create a new transaction
        $transaction = new Transaction([
            'provider' => 'mtn',
            'phone_number' => '260971234567',
            'amount' => 100,
            'currency' => 'ZMW',
            'reference' => 'test-ref',
            'status' => 'pending',
            'metadata' => ['customer_id' => '12345'],
        ]);

        // Save the transaction to trigger setAttribute
        $transaction->save();

        // Get the raw attributes directly from the database
        $rawAttributes = $transaction->getAttributes();

        // Assert that the phone number is encrypted
        $this->assertNotEquals('260971234567', $rawAttributes['phone_number']);
        $this->assertEquals('260971234567', $transaction->phone_number);

        // Assert that the metadata is encrypted
        $this->assertIsString($rawAttributes['metadata']);
        $this->assertNotEquals(json_encode(['customer_id' => '12345']), $rawAttributes['metadata']);
        $this->assertEquals(['customer_id' => '12345'], $transaction->metadata);

        // Verify that we can decrypt the values
        $this->assertEquals('260971234567', Crypt::decrypt($rawAttributes['phone_number']));
    }

    /** @test */
    public function it_decrypts_sensitive_data_when_getting_attributes()
    {
        // Create a transaction with encrypted data
        $phoneNumber = Crypt::encrypt('260971234567');
        $metadata = Crypt::encrypt(json_encode(['customer_id' => '12345']));

        // Insert directly to bypass encryption in the model
        $id = \DB::table('mobile_wallet_transactions')->insertGetId([
            'provider' => 'mtn',
            'phone_number' => $phoneNumber,
            'amount' => 100,
            'currency' => 'ZMW',
            'reference' => 'test-ref',
            'status' => 'pending',
            'metadata' => $metadata,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Retrieve the transaction
        $transaction = Transaction::find($id);

        // Assert that the attributes are decrypted
        $this->assertEquals('260971234567', $transaction->phone_number);
        $this->assertEquals(['customer_id' => '12345'], $transaction->metadata);
    }

    /** @test */
    public function it_handles_array_conversion_with_encrypted_attributes()
    {
        // Create a transaction
        $transaction = new Transaction([
            'provider' => 'mtn',
            'phone_number' => '260971234567',
            'amount' => 100,
            'currency' => 'ZMW',
            'reference' => 'test-ref',
            'status' => 'pending',
            'metadata' => ['customer_id' => '12345'],
        ]);

        // Save the transaction
        $transaction->save();

        // Convert to array
        $array = $transaction->toArray();

        // Assert that the encrypted attributes are decrypted in the array
        $this->assertEquals('260971234567', $array['phone_number']);
        $this->assertEquals(['customer_id' => '12345'], $array['metadata']);
    }

    /** @test */
    public function it_handles_non_encrypted_values_gracefully()
    {
        // Create a transaction with non-encrypted data
        $id = \DB::table('mobile_wallet_transactions')->insertGetId([
            'provider' => 'mtn',
            'phone_number' => '260971234567', // Not encrypted
            'amount' => 100,
            'currency' => 'ZMW',
            'reference' => 'test-ref',
            'status' => 'pending',
            'metadata' => json_encode(['customer_id' => '12345']), // Not encrypted
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Retrieve the transaction
        $transaction = Transaction::find($id);

        // Assert that the attributes are returned as is
        $this->assertEquals('260971234567', $transaction->phone_number);
        $this->assertEquals(['customer_id' => '12345'], $transaction->metadata);
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create the transactions table
        \Schema::create('mobile_wallet_transactions', function ($table) {
            $table->id();
            $table->string('provider');
            $table->string('phone_number');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('reference')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('status');
            $table->text('message')->nullable();
            $table->text('metadata')->nullable();
            $table->string('callback_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        \Schema::dropIfExists('mobile_wallet_transactions');
        
        parent::tearDown();
    }
} 