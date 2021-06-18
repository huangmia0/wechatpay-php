<?php

namespace WeChatPay\Crypto;

use function in_array;
use function openssl_get_cipher_methods;
use function openssl_encrypt;
use function base64_encode;
use function base64_decode;
use function substr;
use function strlen;
use function openssl_decrypt;

use const OPENSSL_RAW_DATA;

use RuntimeException;

/**
 * Aes encrypt/decrypt using `aes-256-gcm` algorithm with additional authenticated data(`aad`).
 */
class AesGcm implements AesInterface
{
    /**
     * Detect the ext-openssl whether or nor including the `aes-256-gcm` algorithm
     *
     * @return void
     * @throws RuntimeException
     */
    private static function preCondition(): void
    {
        if (!in_array(static::ALGO_AES_256_GCM, openssl_get_cipher_methods())) {
            throw new RuntimeException('It looks like the ext-openssl extension missing the `aes-256-gcm` cipher method.');
        }
    }

    /**
     * Encrypts given data with given key, iv and aad, returns a base64 encoded string.
     *
     * @param string $plaintext - Text to encode.
     * @param string $key - The secret key, 32 bytes string.
     * @param string $iv - The initialization vector, 16 bytes string.
     * @param string $aad - The additional authenticated data, maybe empty string.
     *
     * @return string - The base64-encoded ciphertext.
     */
    public static function encrypt(string $plaintext, string $key, string $iv = '', string $aad = ''): string
    {
        static::preCondition();

        $ciphertext = openssl_encrypt($plaintext, static::ALGO_AES_256_GCM, $key, OPENSSL_RAW_DATA, $iv, $tag, $aad, static::BLOCK_SIZE);

        return base64_encode($ciphertext . $tag);
    }

    /**
     * Takes a base64 encoded string and decrypts it using a given key, iv and aad.
     *
     * @param string $ciphertext - The base64-encoded ciphertext.
     * @param string $key - The secret key, 32 bytes string.
     * @param string $iv - The initialization vector, 16 bytes string.
     * @param string $aad - The additional authenticated data, maybe empty string.
     *
     * @return string - The utf-8 plaintext.
     */
    public static function decrypt(string $ciphertext, string $key, string $iv = '', string $aad = ''): string
    {
        static::preCondition();

        $ciphertext = base64_decode($ciphertext);
        $authTag = substr($ciphertext, -static::BLOCK_SIZE);
        $tagLength = strlen($authTag);

        /* Manually checking the length of the tag, because the `openssl_decrypt` was mentioned there, it's the caller's responsibility. */
        if ($tagLength > static::BLOCK_SIZE || ($tagLength < 12 && $tagLength !== 8 && $tagLength !== 4)) {
            throw new RuntimeException('The inputs `$ciphertext` incomplete, the bytes length must be one of 16, 15, 14, 13, 12, 8 or 4.');
        }

        return openssl_decrypt(substr($ciphertext, 0, -static::BLOCK_SIZE), static::ALGO_AES_256_GCM, $key, OPENSSL_RAW_DATA, $iv, $authTag, $aad);
    }
}