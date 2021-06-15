<?php

namespace WechatPay\GuzzleMiddleware\Crypto;

use function hash_init;
use function hash_update;
use function hash_final;
use function array_key_exists;
use function strtoupper;

use const HASH_HMAC;

const WCP_SUPPORT_HASHES = [Hash::ALGO_HMAC_SHA256 => 'hmac', Hash::ALGO_MD5 => 'md5'];

/**
 * Crypto hash functions utils.
 * [Specification]{@link https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=4_3}
 */
class Hash
{
    /** @var string - hashing `MD5` algorithm */
    const ALGO_MD5 = 'MD5';

    /** @var string - hashing `HMAC-SHA256` algorithm */
    const ALGO_HMAC_SHA256 = 'HMAC-SHA256';

    /**
     * Calculate the input string with an optional secret `key` in MD5,
     * when the `key` is Falsey, this method works as normal `MD5`.
     *
     * @param string $thing - The input string.
     * @param string [$key = ''] - The secret key string.
     * @param boolean|int|string [$agency = false] - The secret **key** is from wework, placed with `true` or better of the `AgentId` value.
     *                                               [spec]{@link https://work.weixin.qq.com/api/doc/90000/90135/90281}
     *
     * @return string - The data signature
     */
    public static function md5(string $thing, string $key = '', $agency = false): string
    {
        $ctx = hash_init(static::ALGO_MD5);

        hash_update($ctx, $thing) && $key && hash_update($ctx, $agency ? '&secret=' : '&key=') && hash_update($ctx, $key);

        return hash_final($ctx);
    }

    /**
     * Calculate the input string with a secret `key` as of `algorithm` string which is one of the 'sha256', 'sha512' etc.
     *
     * @param string $thing - The input string.
     * @param string $key - The secret key string.
     * @param string [$algorithm = sha256] - The algorithm string, default is `sha256`.
     *
     * @return string - The data signature
     */
    public static function hmac(string $thing, string $key, string $algorithm = 'sha256'): string
    {
        $ctx = hash_init($algorithm, HASH_HMAC, $key);

        hash_update($ctx, $thing) && hash_update($ctx, '&key=') && hash_update($ctx, $key);

        return hash_final($ctx);
    }

    /**
     * Utils of the data signature calculation.
     *
     * @param string $type - The sign type, one of the `MD5` or `HMAC-SHA256`.
     * @param string $data - The input data.
     * @param string $key - The secret key string.
     *
     * @return ?string - The data signature in UPPERCASE.
     */
    public static function sign(string $type, string $data, string $key): ?string
    {
        return array_key_exists($type, WCP_SUPPORT_HASHES) ? strtoupper(static::{WCP_SUPPORT_HASHES[$type]}($data, $key)) : null;
    }
}
