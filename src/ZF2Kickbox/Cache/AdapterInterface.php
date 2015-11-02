<?php

namespace ZF2Kickbox\Cache;

/**
 * @author      Boris Yonchev <boris@yonchev.me>
 */
interface AdapterInterface
{
    /**
     * @param string $email
     * @param bool   $isValid
     */
    public function cacheVerification($email, $isValid);

    /**
     * @param string $email
     *
     * @return bool|null
     */
    public function getCachedVerification($email);
}