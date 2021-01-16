<?php

namespace Ujamii\ExternalBlog\Eel;

use Neos\Cache\Exception\NoSuchCacheException;
use Neos\Eel\ProtectedContextAwareInterface;
use Ujamii\ExternalBlog\Service\BlogDataService;
use Neos\Flow\Annotations as Flow;

class BlogPostHelper implements ProtectedContextAwareInterface
{

    /**
     * @var BlogDataService
     * @Flow\Inject
     */
    protected $blogDataService;

    /**
     * @param string|null $url
     * @param int|null $maxItems
     * @param int|null $offset
     *
     * @return array
     * @throws NoSuchCacheException
     */
    public function getBlogPosts(?string $url, ?int $maxItems = 10, ?int $offset = 0): array
    {
        $posts = [];
        if (!empty($url)) {
            try {
                $posts = $this->blogDataService->getPostsFromUrl($url, $maxItems);
                $posts = array_slice($posts, $offset, $maxItems);
            } catch (NoSuchCacheException $e) {
                //TODO ?
                throw $e;
            }
        }

        return $posts;
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
