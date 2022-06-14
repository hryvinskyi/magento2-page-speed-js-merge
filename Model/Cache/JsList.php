<?php
/**
 * Copyright (c) 2020-2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeedJsMerge\Model\Cache;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Zend_Cache;
use Zend_Cache_Core;
use Zend_Cache_Exception;

/**
 * Class JsList
 */
class JsList extends Zend_Cache
{
    private DirectoryList $directoryList;
    private array $frontendOptions;
    private array $backendOptions;

    /**
     * @param DirectoryList $directoryList
     * @param File $file
     * @param array $frontendOptions
     * @param array $backendOptions
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file,
        array $frontendOptions = [],
        array $backendOptions = []
    ) {
        $this->frontendOptions = $frontendOptions;
        $this->backendOptions = $backendOptions;
        $this->directoryList = $directoryList;
        $directoryPath = $this->directoryList->getRoot() . '/var/pagespeed_cache';
        if (!$file->isDirectory($directoryPath)) {
            $file->createDirectory($directoryPath);
        }
    }

    /**
     * @return array
     */
    public function getFrontendOptions(): array
    {
        return array_merge(['automatic_serialization' => true], $this->frontendOptions);
    }

    /**
     * @return array
     */
    public function getBackendOptions(): array
    {
        return array_merge(['cache_dir' => $this->directoryList->getRoot() . '/var/pagespeed_cache'], $this->backendOptions);
    }

    /**
     * @return Zend_Cache_Core
     * @throws Zend_Cache_Exception
     */
    public function getCache(): Zend_Cache_Core
    {
        return self::factory(
            'Core',
            'File',
            $this->getFrontendOptions(),
            $this->getBackendOptions()
        );
    }
}
