<?php

namespace SCFL\App\Model\Traits;

use Psr\SimpleCache\CacheInterface;

/**
 * Trait CacheTraits
 * @package SCFL\App\Model\Traits
 */
trait CacheTraits
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }
}
