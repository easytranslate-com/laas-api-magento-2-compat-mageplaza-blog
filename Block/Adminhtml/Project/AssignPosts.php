<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project;

use EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project\Tab\Posts;
use EasyTranslate\CompatMageplazaBlog\Model\ResourceModel\Posts as PostsResource;
use EasyTranslate\Connector\Block\Adminhtml\Project\AbstractBlock;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\BlockInterface;

/**
 * @see \EasyTranslate\Connector\Block\Adminhtml\Project\AssignedProducts
 */
class AssignPosts extends AbstractBlock
{
    private const INCLUDED_POSTS = 'included_posts[]';

    /**
     * @var Posts
     */
    private $blockGrid;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var PostsResource
     */
    private $postsResource;

    public function __construct(
        Context $context,
        ProjectGetter $projectGetter,
        SerializerInterface $serializer,
        PostsResource $postsResource,
        array $data = []
    ) {
        parent::__construct($context, $projectGetter, $data);
        $this->serializer    = $serializer;
        $this->postsResource = $postsResource;
    }

    /**
     * @throws LocalizedException
     */
    public function getBlockGrid(): BlockInterface
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                Posts::class,
                'project.easytranslateposts.grid'
            );
        }

        return $this->blockGrid;
    }

    public function getEntitiesJson(): string
    {
        $project = $this->projectGetter->getProject();
        if (!$project) {
            return $this->serializer->serialize([]);
        }

        return $this->serializer->serialize($this->postsResource->getPosts($project));
    }

    public function getInputName(): string
    {
        return 'mageplaza_selected_posts';
    }

    public function getGridParam(): string
    {
        return self::INCLUDED_POSTS;
    }
}
