<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project;

use EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project\Tab\Posts;
use EasyTranslate\CompatMageplazaBlog\Model\Posts as PostsModel;
use EasyTranslate\Connector\Block\Adminhtml\Project\AbstractBlock;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\BlockInterface;

/**
 * @see \EasyTranslate\Connector\Block\Adminhtml\Project\AssignedProducts
 */
class AssignPosts extends AbstractBlock
{
    private const INCLUDED_MAGEPLAZA_POSTS = 'included_mageplaza_posts[]';

    /**
     * @var Posts
     */
    private $blockGrid;

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var PostsModel
     */
    private $posts;

    public function __construct(
        Context $context,
        ProjectGetter $projectGetter,
        EncoderInterface $jsonEncoder,
        PostsModel $posts,
        array $data = []
    ) {
        parent::__construct($context, $projectGetter, $data);
        $this->jsonEncoder = $jsonEncoder;
        $this->posts       = $posts;
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
            return $this->jsonEncoder->encode([]);
        }

        return $this->jsonEncoder->encode($this->posts->getPosts($project));
    }

    public function getInputName(): string
    {
        return 'mageplaza_selected_posts';
    }

    public function getGridParam(): string
    {
        return self::INCLUDED_MAGEPLAZA_POSTS;
    }
}
