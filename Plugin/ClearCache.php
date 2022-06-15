<?php

declare(strict_types=1);

namespace Hryvinskyi\PageSpeedJsMerge\Plugin;

use Hryvinskyi\PageSpeedApi\Model\CacheInterface;
use Hryvinskyi\PageSpeedJsMerge\Model\Cache\JsList as JsListCache;
use Magento\Deploy\Model\Mode;
use Magento\Framework\Filesystem\Io\File;

class ClearCache
{
    private \Zend_Cache_Core $jsListCache;
    private File $file;
    private CacheInterface $cache;

    public function __construct(JsListCache $jsListCache, File $file, CacheInterface $cache)
    {
        $this->jsListCache = $jsListCache->getCache();
        $this->file = $file;
        $this->cache = $cache;
    }

    /**
     * @param Mode $subject
     * @return array
     * @throws \Zend_Cache_Exception
     * @noinspection PhpUnusedParameterInspection
     */
    public function beforeEnableProductionMode(Mode $subject): array
    {
        $this->cleanCache();
        return [];
    }

    /**
     * @param Mode $subject
     * @return array
     * @throws \Zend_Cache_Exception
     * @noinspection PhpUnusedParameterInspection
     */
    public function beforeEnableProductionModeMinimal(Mode $subject): array
    {
        $this->cleanCache();
        return [];
    }

    /**
     * @param Mode $subject
     * @return array
     * @throws \Zend_Cache_Exception
     * @noinspection PhpUnusedParameterInspection
     */
    public function beforeEnableDeveloperMode(Mode $subject): array
    {
        $this->cleanCache();
        return [];
    }

    /**
     * @param Mode $subject
     * @return array
     * @throws \Zend_Cache_Exception
     * @noinspection PhpUnusedParameterInspection
     */
    public function beforeEnableDefaultMode(Mode $subject): array
    {
        $this->cleanCache();
        return [];
    }

    /**
     * @return void
     * @throws \Zend_Cache_Exception
     * @noinspection PhpUnusedParameterInspection
     */
    public function cleanCache(): void
    {
        $this->jsListCache->clean('all', [CacheInterface::CACHE_TAG]);
        $this->file->rmdir($this->cache->getRootCachePath(), true);
    }
}