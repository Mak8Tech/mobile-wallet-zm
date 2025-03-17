<?php

namespace Mak8Tech\MobileWalletZm\Security;

use InvalidArgumentException;
use Mak8Tech\MobileWalletZm\Contracts\SignatureVerifier;

class SignatureVerifierFactory
{
    /**
     * Create a signature verifier for the specified provider
     *
     * @param  string  $provider  The payment provider (mtn, airtel, zamtel)
     * @param  array  $config  The provider-specific configuration
     *
     * @throws InvalidArgumentException
     */
    public static function create(string $provider, array $config): SignatureVerifier
    {
        return match (strtolower($provider)) {
            'mtn' => new MTNSignatureVerifier($config['api_secret'] ?? ''),
            'airtel' => new AirtelSignatureVerifier(
                $config['api_key'] ?? '',
                $config['api_secret'] ?? ''
            ),
            'zamtel' => new ZamtelSignatureVerifier($config['api_secret'] ?? ''),
            default => throw new InvalidArgumentException("Unsupported payment provider: {$provider}")
        };
    }
}
