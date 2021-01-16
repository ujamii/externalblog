<?php

namespace Ujamii\ExternalBlog\Service;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\CurlEngine;
use Psr\Log\LoggerInterface;
use Ujamii\ExternalBlog\Domain\Dto\BlogPost;

class BlogDataService
{
    /**
     * Cache identifier for this class.
     */
    private const CACHE_IDENTIFIER = 'Ujamii_ExternalBlog_BlogDataService';

    /**
     * @var Browser
     */
    protected $browser;

    /**
     * @var CacheManager
     * @Flow\Inject
     */
    protected $cacheManager;

    /**
     * @var LoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * BlogDataService constructor.
     *
     * @param Browser $browser
     */
    public function __construct(Browser $browser)
    {
        $this->browser = $browser;
        $this->browser->setRequestEngine(new CurlEngine());
    }

    /**
     * @param string $url
     * @param bool $useCache
     *
     * @return array
     * @throws \Neos\Cache\Exception\NoSuchCacheException
     */
    public function getPostsFromUrl(string $url, $useCache = true)
    {
        $xmlString = $this->getCachedOrFreshXmlData($url, $useCache, 'postList');
        $xmlObject = new \SimpleXMLElement($xmlString, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOCDATA);

        if ($xmlObject->channel) {
            $posts = [];
            foreach ($xmlObject->channel->item as $xmlItem) {
                $posts[] = BlogPost::fromRss($xmlItem);
            }
        } else {
            if ( ! in_array('http://www.w3.org/2005/Atom', $xmlObject->getDocNamespaces(), true)
                 && ! in_array('http://purl.org/atom/ns#', $xmlObject->getDocNamespaces(), true)
            ) {
                throw new \Exception('Invalid feed: ' . $url);
            }
            foreach ($xmlObject->entry as $xmlItem) {
                $posts[] = BlogPost::fromAtom($xmlItem);
            }
        }

        return $posts;
    }

    /**
     * @param string $url
     * @param bool $useCache
     * @param string $cacheName
     *
     * @return string
     * @throws \Neos\Cache\Exception\NoSuchCacheException
     */
    protected function getCachedOrFreshXmlData(string $url, bool $useCache = true, string $cacheName = ''): string
    {
        /** @var VariableFrontend $xmlCache */
        $xmlCache = $this->cacheManager->getCache(self::CACHE_IDENTIFIER);
        // we add the short md5 of the url here, to prevent cache collisions for different content elements
        $cacheName .= substr(md5($url), 0, 10);
        if ($useCache) {
            if ($xmlCache->has($cacheName)) {
                return $xmlCache->get($cacheName);
            }
        }

        $return = [];
        try {
            $return = $this->browser->request($url)->getBody()->getContents();
            if ( ! empty($return)) {
                $xmlCache->set($cacheName, $return);
            }
        } catch (\Exception $e) {
            // did not work...
            $this->logger->error($e->getMessage());
        }

        return $return;
    }

}
