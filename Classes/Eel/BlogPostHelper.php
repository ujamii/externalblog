<?php

namespace Ujamii\ExternalBlog\Eel;

use Neos\Cache\Exception\NoSuchCacheException;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\ImageRepository;
use Psr\Log\LoggerInterface;
use Ujamii\ExternalBlog\Service\BlogDataService;

class BlogPostHelper implements ProtectedContextAwareInterface
{

    /**
     * Name for the target collection of imported images.
     */
    protected const DEFAULT_COLLECTION_NAME = 'imported';

    /**
     * @var BlogDataService
     * @Flow\Inject
     */
    protected $blogDataService;

    /**
     * @var ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;

    /**
     * @var ImageRepository
     * @Flow\Inject
     */
    protected $imageRepository;

    /**
     * @var LoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

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
        if ( ! empty($url)) {
            try {
                $posts = $this->blogDataService->getPostsFromUrl($url, $maxItems);
                $posts = array_slice($posts, $offset, $maxItems);
            } catch (NoSuchCacheException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $posts;
    }

    /**
     * @param string $remoteImageUrl
     * @param string $collectionName
     *
     * @return Image|null
     */
    public function importRemoteImage(string $remoteImageUrl, string $collectionName = self::DEFAULT_COLLECTION_NAME): ?Image
    {
        try {
            $existingImage = $this->imageRepository->findOneByTitle($remoteImageUrl);
            if (null !== $existingImage) {
                return $existingImage;
            }
            $resource = $this->resourceManager->importResource($remoteImageUrl);
            if ($resource) {
                $image = $this->imageRepository->findOneByResourceSha1($resource->getSha1());
                if (null === $image) {
                    $image = new Image($resource);
                    $image->setTitle($remoteImageUrl);
                    $this->imageRepository->add($image);

                    $imageCollection = $this->assetCollectionRepository->findOneByTitle($collectionName);
                    if ($imageCollection !== null) {
                        $imageCollection->addAsset($image);
                        $this->assetCollectionRepository->update($imageCollection);
                    }

                    $this->persistenceManager->persistAll();
                }

                return $image;
            }
        } catch (\Exception $e) {
            // no image :-(
            $this->logger->error("Image could not be imported. Reason: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * @param string $methodName
     *
     * @return bool
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
