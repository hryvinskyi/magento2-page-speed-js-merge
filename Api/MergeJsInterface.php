<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\PageSpeedJsMerge\Api;

interface MergeJsInterface
{
    public const FLAG_IGNORE_MERGE = 'data-pagespeed-ignore-merge';

    /**
     * @param string $html
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function inline(string &$html): void;

    /**
     * @param string $html
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function merge(string &$html): void;
}
