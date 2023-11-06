<?php

namespace Ujamii\ExternalBlog\Domain\Dto;

class BlogPost
{

    /**
     * @var string
     */
    protected $guid = '';

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var \DateTime
     */
    protected $pubDate;

    /**
     * @var string
     */
    protected $link = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $content = '';

    /**
     * @var array
     */
    protected $categories = [];

    /**
     * @var string
     */
    protected $author = '';

    /**
     * @var array
     */
    protected $media = [];

    /**
     * Creates a BlobPost DTO from a single RSS item element.
     *
     * @param \SimpleXMLElement $xmlElement
     *
     * @return BlogPost
     */
    public static function fromRss(\SimpleXMLElement $xmlElement): BlogPost
    {
        self::adjustNamespaces($xmlElement);
        $post = new BlogPost();

        $post->setGuid((string)$xmlElement->guid);
        $post->setTitle((string)$xmlElement->title);
        $post->setContent((string)$xmlElement->{'content:encoded'});
        $post->setDescription((string)$xmlElement->description);
        $post->setAuthor((string)$xmlElement->author);
        $post->setLink((string)$xmlElement->link);
        if (isset($xmlElement->{'dc:date'})) {
            $post->setPubDate(\DateTime::createFromFormat(DATE_ATOM, $xmlElement->{'dc:date'}));
        } elseif (isset($xmlElement->pubDate)) {
            $post->setPubDate(\DateTime::createFromFormat(DATE_RSS, $xmlElement->pubDate));
        }
        $categories = [];
        foreach ($xmlElement->category as $category) {
            $categories[] = (string)$category;
        }
        $post->setCategories($categories);
        $media = [];
        foreach ($xmlElement->enclosure as $mediaItem) {
            $media[] = [
                'url'         => (string)$mediaItem['url'],
                'sizeInBytes' => (int)$mediaItem['length'],
                'mimeType'    => (string)$mediaItem['type'],
            ];
        }
        $post->setMedia($media);

        return $post;
    }

    /**
     * Creates a BlobPost DTO from a single Atom entry element.
     *
     * @param \SimpleXMLElement $xmlElement
     *
     * @return BlogPost
     */
    public static function fromAtom(\SimpleXMLElement $xmlElement): BlogPost
    {
        self::adjustNamespaces($xmlElement);
        $post = new BlogPost();

        $post->setGuid((string)$xmlElement->id);
        $post->setTitle((string)$xmlElement->title);
        $post->setContent((string)$xmlElement->content);
        $post->setDescription((string)$xmlElement->summary);
        $post->setAuthor((string)$xmlElement->author->name);
        $media = [];
        foreach ($xmlElement->link as $link) {
            $type = (string)$link['rel'];
            switch ($type) {

                default:
                case 'self':
                    $post->setLink((string)$link['href']);
                    break;

                case 'enclosure':
                    $media[] = [
                        'url'         => (string)$link['href'],
                        'sizeInBytes' => (int)$link['length'],
                        'mimeType'    => (string)$link['type'],
                    ];
                    break;

                case 'alternate':
                case 'related':
                case 'via':
                    // we dont care for those
                    break;
            }
        }
        $post->setMedia($media);
        $post->setPubDate(\DateTime::createFromFormat(DATE_ATOM, $xmlElement->published));
        $categories = [];
        foreach ($xmlElement->category as $category) {
            $categories[] = (string)$category['label'];
        }
        $post->setCategories($categories);

        return $post;
    }

    /**
     * Generates better accessible namespaced tags.
     * @see https://github.com/dg/rss-php/blob/106bd43eb70c2149bac12356df4022d077f2c709/src/Feed.php#L246
     *
     * @param \SimpleXMLElement
     */
    protected static function adjustNamespaces(\SimpleXMLElement $xmlElement)
    {
        foreach ($xmlElement->getNamespaces(true) as $prefix => $ns) {
            $children = $xmlElement->children($ns);
            foreach ($children as $tag => $content) {
                $xmlElement->{$prefix . ':' . $tag} = $content;
            }
        }
    }

    /**
     * @return string
     */
    public function getGuid(): string
    {
        return $this->guid;
    }

    /**
     * @param string $guid
     */
    public function setGuid(string $guid): void
    {
        $this->guid = $guid;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return \DateTime
     */
    public function getPubDate(): \DateTime
    {
        return $this->pubDate;
    }

    /**
     * @param \DateTime $pubDate
     */
    public function setPubDate(\DateTime $pubDate): void
    {
        $this->pubDate = $pubDate;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param array $categories
     */
    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }

    /**
     * @return array ['url' => 'absoluteUrl', 'sizeInBytes' => 123, 'mimeType' => 'image/jpeg']
     */
    public function getMedia(): array
    {
        return $this->media;
    }

    /**
     * @param array $media
     */
    public function setMedia(array $media): void
    {
        $this->media = $media;
    }

    /**
     * @return string
     */
    public function getFirstMediaUrl(): string
    {
        if (count($this->media) > 0) {
            return $this->media[0]['url'];
        }

        return '';
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @param string $author
     */
    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

}
