<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset faker unique cache to prevent unique constraint violations between tests
        $this->faker->unique(true);

        $this->usePassportTestKeys();
    }

    /**
     * Give Passport a key pair, so the MCP endpoint's OAuth guard can be built.
     *
     * Passport loads its public key whenever that guard is constructed, which
     * happens on every unauthenticated MCP request, not only on OAuth ones.
     * Generated once per process: key generation is slow, and nothing depends
     * on the pair being fresh.
     */
    private function usePassportTestKeys(): void
    {
        static $keys = null;

        if ($keys === null) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $privateKey);
            $keys = ['private' => $privateKey, 'public' => openssl_pkey_get_details($key)['key']];
        }

        config()->set('passport.private_key', $keys['private']);
        config()->set('passport.public_key', $keys['public']);
    }
}
