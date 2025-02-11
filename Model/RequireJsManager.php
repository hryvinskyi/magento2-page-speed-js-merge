<?php
/**
 * Copyright (c) 2025. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

namespace Hryvinskyi\PageSpeedJsMerge\Model;

use Hryvinskyi\Base\Helper\Json;
use Hryvinskyi\PageSpeedApi\Api\Finder\JsInterface as JsFinderInterface;
use Hryvinskyi\PageSpeedApi\Api\Finder\Result\TagInterface;
use Hryvinskyi\PageSpeedApi\Api\GetFileContentByUrlInterface;
use Hryvinskyi\PageSpeedApi\Api\GetLocalPathFromUrlInterface;
use Hryvinskyi\PageSpeedApi\Api\Html\ReplaceIntoHtmlInterface;
use Hryvinskyi\PageSpeedApi\Api\IsInternalUrlInterface;
use Hryvinskyi\PageSpeedApi\Model\CacheInterface;
use Hryvinskyi\PageSpeedJsMerge\Api\ConfigInterface;
use Hryvinskyi\PageSpeedJsMerge\Model\Cache\JsList as JsListCache;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutInterface;

class RequireJsManager
{
    /**
     * Const
     */
    public const SCRIPT_TAG_DATA_KEY = 'data-cmp-requirejs-key';
    public const CACHE_KEY_PREFIX = 'PAGESPEED_JS_MERGE_REQUIREJS_';
    public const TAG_VALUE_PLACEHOLDER = '{{hryvinskyi-pagespeed-tags-placeholder}}';
    public const LIB_JS_BUILD_SCRIPT = 'mage/requirejs/static.js';
    public const REQUIREJS_STORAGE_DIR = 'requirejs';

    private ConfigInterface $config;
    private \Zend_Cache_Core $jsListCache;
    private Context $context;
    private IsInternalUrlInterface $isInternalUrl;
    private GetLocalPathFromUrlInterface $getLocalPathFromUrl;
    private GetFileContentByUrlInterface $getFileContentByUrl;
    private JsFinderInterface $jsFinder;
    private ReplaceIntoHtmlInterface $replaceIntoHtml;

    /**
     * @param ConfigInterface $config
     * @param JsListCache $jsListCache
     * @param Context $context
     * @param JsFinderInterface $jsFinder
     * @param IsInternalUrlInterface $isInternalUrl
     * @param GetLocalPathFromUrlInterface $getLocalPathFromUrl
     * @param GetFileContentByUrlInterface $getFileContentByUrl
     * @param ReplaceIntoHtmlInterface $replaceIntoHtml
     * @throws \Zend_Cache_Exception
     */
    public function __construct(
        ConfigInterface $config,
        JsListCache $jsListCache,
        Context $context,
        JsFinderInterface $jsFinder,
        IsInternalUrlInterface $isInternalUrl,
        GetLocalPathFromUrlInterface $getLocalPathFromUrl,
        GetFileContentByUrlInterface $getFileContentByUrl,
        ReplaceIntoHtmlInterface $replaceIntoHtml
    ) {
        $this->config = $config;
        $this->jsListCache = $jsListCache->getCache();
        $this->context = $context;
        $this->jsFinder = $jsFinder;
        $this->isInternalUrl = $isInternalUrl;
        $this->getLocalPathFromUrl = $getLocalPathFromUrl;
        $this->getFileContentByUrl = $getFileContentByUrl;
        $this->replaceIntoHtml = $replaceIntoHtml;
    }

    /**
     * @param LayoutInterface $layout
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRouteKeyByLayout(LayoutInterface $layout): string
    {
        $handleList = $layout->getUpdate()->getHandles();
        $handleList = array_slice($handleList, 0, 2);
        $result = implode('____', $handleList);
        $result .= '___' . md5($this->context->getDesignPackage()->getDesignTheme()->getCode());
        $result .= '___' . $this->context->getStoreManager()->getStore()->getId();

        return $result;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRouteKeyByCurrentContext(): string
    {
        return $this->getRouteKeyByLayout($this->context->getLayout());
    }

    /**
     * @param string $routeKey
     *
     * @return bool
     */
    public function isDataExists(string $routeKey): bool
    {
        return (bool)$this->jsListCache->test(self::CACHE_KEY_PREFIX . $routeKey);
    }

    /**
     * @param array $list
     * @param string $routeKey
     *
     * @return bool
     * @throws \Zend_Cache_Exception
     */
    public function saveUrlList(array $list, string $routeKey): bool
    {
        return $this->jsListCache->save(
            Json::encode(array_unique($list)),
            self::CACHE_KEY_PREFIX . $routeKey,
            [CacheInterface::CACHE_TAG],
            null
        );
    }

    /**
     * @param string|null $routeKey
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loadUrlList(?string $routeKey): array
    {
        if ($routeKey === null) {
            $routeKey = $this->getRouteKeyByCurrentContext();
        }

        $data = $this->jsListCache->load(self::CACHE_KEY_PREFIX . $routeKey);

        if (!is_string($data)) {
            return [];
        }

        try {
            $result = Json::decode($data);
        } catch (\Exception $e) {
            return [];
        }

        return $result;
    }

    /**
     * @param string $routeKey
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loadFileList(string $routeKey): array
    {
        $list = $this->loadUrlList($routeKey);
        $result = [];

        foreach ($list as $url) {
            if ($this->isInternalUrl->execute($url) === false) {
                continue;
            }

            $result[] = $this->getLocalPathFromUrl->execute($url);
        }

        return $result;
    }

    /**
     * @param string $routeKey
     * @param string $fileExtension
     * @param \Closure|null $callbackOnContent
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getInlineConfig(
        string $routeKey,
        string $fileExtension = '.js',
        \Closure $callbackOnContent = null
    ): array {
        $urlList = $this->loadUrlList($routeKey);

        if (count($urlList) === 0) {
            return [];
        }

        $baseUrl = array_shift($urlList);
        $excludeAnchorList = $this->config->getExcludeAnchors();
        $config = [];
        foreach ($urlList as $url) {
            if ($this->isInternalUrl->execute($url) === false) {
                continue;
            }
            $currentFileExtension = substr($url, strlen($fileExtension) * -1);
            if ($currentFileExtension !== $fileExtension) {
                continue;
            }

            $baseUrlOffset = count(explode('/', $baseUrl)) - 1;
            $urlParts = explode('/', $url);
            $key = implode('/', array_slice($urlParts, $baseUrlOffset));
            $content = $this->getFileContentByUrl->execute($url);
            $isExit = false;
            foreach ($excludeAnchorList as $anchor) {
                if (false !== strpos($url, $anchor)) {
                    $isExit = true;
                    break;
                }
            }

            if ($isExit) {
                $config[$key] = $content;
                continue;
            }

            if ($callbackOnContent !== null) {
                try {
                    $content = $callbackOnContent($content);
                } catch (\Exception $e) {
                }
            }

            $config[$key] = $content;
        }

        if (array_key_exists(self::LIB_JS_BUILD_SCRIPT, $config)) {
            unset($config[self::LIB_JS_BUILD_SCRIPT]);
        }

        return $config;
    }

    /**
     * @param string $routeKey
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRequireJsContent(string $routeKey): string
    {
        $config = [
            'jsbuild' => $this->getInlineConfig($routeKey),
            'text' => $this->getInlineConfig($routeKey, '.html'),
        ];

        return "require.config({config:" . Json::encode($config) . "});" .
            "require.config({bundles:{'mage/requirejs/static':['jsbuild','buildTools','text','statistician']}," .
            "deps:['jsbuild']});";
    }

    /**
     * @param ResponseHttp $response
     *
     * @return void
     * @throws \Exception
     */
    public function processWithResponseHttp(ResponseHttp $response): void
    {
        $header = $response->getHeader('X-Magento-Tags');
        $pageCacheTagList = $this->context->getUrlBuilder()->getCurrentUrl();
        if (is_array($header) || $header instanceof \ArrayIterator) {
            $list = [];
            foreach ($header as $value) {
                $list[] = $value->getFieldValue();
            }
            $pageCacheTagList .= ',' . implode(',', $list);
        } elseif ($header) {
            $pageCacheTagList .= ',' . $header->getFieldValue();
        }

        $html = $response->getBody();
        $tagsList = $this->jsFinder->findInline($html);
        $replaceData = [];

        foreach ($tagsList as $tag) {
            /** @var TagInterface $tag */
            $attributes = $tag->getAttributes();
            if (!array_key_exists(self::SCRIPT_TAG_DATA_KEY, $attributes)) {
                continue;
            }
            preg_match('/^(<script[^>]*?>)(.*)(<\/script>)$/is', $tag->getContent(), $matches);
            if (count($matches) === 0) {
                continue;
            }
            $content = $matches[2];
            $replaceData[] = [
                'start'   => $tag->getStart(),
                'end'     => $tag->getEnd(),
                'content' => $matches[1] . $content . $matches[3],
            ];
        }

        foreach (array_reverse($replaceData) as $replaceElementData) {
            $html = $this->replaceIntoHtml->execute(
                $html,
                $replaceElementData['content'],
                $replaceElementData['start'],
                $replaceElementData['end']
            );
        }
        $html = str_replace(self::TAG_VALUE_PLACEHOLDER, $pageCacheTagList, $html);
        $response->setBody($html);
    }
}
