<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeedJsMerge\Model;

use Hryvinskyi\PageSpeedJsMerge\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    /**
     * Configuration paths
     */
    public const XML_CONF_MERGE_ENABLED = 'hryvinskyi_pagespeed/js/merge_enabled';
    public const XML_CONF_MINIFY_ENABLED = 'hryvinskyi_pagespeed/js/minify_enabled';
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function isMergeJsEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_CONF_MERGE_ENABLED, $scopeType, $scopeCode);
    }

    /**
     * @inheritdoc
     */
    public function isMinifyJsEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_CONF_MINIFY_ENABLED, $scopeType, $scopeCode);
    }

    public function isMergeInlineJsEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return false;
    }

    public function getInlineMaxLength($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): int
    {
        return 500000;
    }

    public function isCompressionEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return true;
    }

    public function isDeferEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return true;
    }

    public function getExcludeAnchors($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): array
    {
        return [];
    }

    public function getFilePermission($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): int
    {
        return 0755;
    }

    public function getFolderPermission($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): int
    {
        return 0755;
    }
}
