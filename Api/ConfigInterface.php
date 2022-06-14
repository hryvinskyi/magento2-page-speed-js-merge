<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

namespace Hryvinskyi\PageSpeedJsMerge\Api;

use Magento\Store\Model\ScopeInterface;

interface ConfigInterface
{
    /**
     * @param $scopeCode
     * @param string $scopeType
     * @return bool
     */
    public function isMergeJsEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool;

    /**
     * @param $scopeCode
     * @param string $scopeType
     * @return bool
     */
    public function isMinifyJsEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool;

    /**
     * @param $scopeCode
     * @param string $scopeType
     * @return bool
     */
    public function isMergeInlineJsEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool;

    /**
     * @param $scopeCode
     * @param string $scopeType
     * @return int
     */
    public function getInlineMaxLength($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): int;

    /**
     * @param $scopeCode
     * @param string $scopeType
     * @return bool
     */
    public function isCompressionEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool;

    /**
     * @param $scopeCode
     * @param string $scopeType
     * @return bool
     */
    public function isDeferEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool;

    /**
     * @param $scopeCode
     * @param string $scopeType
     * @return array
     */
    public function getExcludeAnchors($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): array;

    /**
     * @param $scopeCode
     * @param string $scopeType
     * @return int
     */
    public function getFilePermission($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): int;

    /**
     * @param $scopeCode
     * @param string $scopeType
     * @return int
     */
    public function getFolderPermission($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): int;
}
