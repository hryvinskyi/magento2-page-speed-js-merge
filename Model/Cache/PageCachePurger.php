<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

namespace Hryvinskyi\PageSpeedJsMerge\Model\Cache;

use Magento\CacheInvalidate\Model\PurgeCache as VarnishPurgeCache;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\PageCache\Model\Cache\Type as BuiltInPageCache;
use Magento\PageCache\Model\Config as PageCacheConfig;

class PageCachePurger
{
    public const LITESPEED_CACHE_CONFIG = 'LITESPEED';
    private PageCacheConfig $pageCacheConfig;
    private VarnishPurgeCache $varnishPurgeCache;
    private BuiltInPageCache $builtInPageCache;
    private EventManagerInterface $eventManager;

    /**
     * @param PageCacheConfig $pageCacheConfig
     * @param VarnishPurgeCache $varnishPurgeCache
     * @param BuiltInPageCache $builtInPageCache
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        PageCacheConfig $pageCacheConfig,
        VarnishPurgeCache $varnishPurgeCache,
        BuiltInPageCache $builtInPageCache,
        EventManagerInterface $eventManager
    ) {
        $this->pageCacheConfig = $pageCacheConfig;
        $this->varnishPurgeCache = $varnishPurgeCache;
        $this->builtInPageCache = $builtInPageCache;
        $this->eventManager = $eventManager;
    }

    /**
     * @param array $tags
     * @return void
     */
    public function purgeByTags(array $tags): void
    {
        if ($this->pageCacheConfig->isEnabled() === false) {
            return;
        }

        $type = $this->pageCacheConfig->getType();
        switch ($type) {
            case PageCacheConfig::VARNISH:
                $this->varnishPurgeByTagListString($tags);
                break;
            case PageCacheConfig::BUILT_IN:
                $this->builtInPageCachePurgeByTagListString($tags);
                break;
            case self::LITESPEED_CACHE_CONFIG:
                $this->eventManager->dispatch(
                    'litemage_purge',
                    ['tags' => $tags, 'reason' => 'Flush LiteSpeed Cache']
                );
                break;
        }
    }

    /**
     * @param array $tags
     * @return void
     */
    private function varnishPurgeByTagListString(array $tags): void
    {
        $this->varnishPurgeCache->sendPurgeRequest(implode(',', $tags));
    }

    /**
     * @param array $tags
     * @return void
     */
    private function builtInPageCachePurgeByTagListString(array $tags): void
    {
        $this->builtInPageCache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, $tags);
    }
}
