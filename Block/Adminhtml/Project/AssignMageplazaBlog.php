<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project;

use EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project\Tab\MageplazaBlogs;
use EasyTranslate\CompatMageplazaBlog\Model\ResourceModel\MageplazaPosts;
use EasyTranslate\Connector\Block\Adminhtml\Project\AbstractBlock;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\BlockInterface;

/**
 * @see \EasyTranslate\Connector\Block\Adminhtml\Project\AssignedProducts
 */
class AssignMageplazaBlog extends AbstractBlock
{
    private const INCLUDED_MAGEPLAZA_BLOGS = 'included_mageplaza_blogs[]';

    /**
     * @var MageplazaBlogs
     */
    private $blockGrid;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var MageplazaPosts
     */
    private $mageplazaPosts;

    public function __construct(
        Context $context,
        ProjectGetter $projectGetter,
        SerializerInterface $serializer,
        MageplazaPosts $mageplazaPosts,
        array $data = []
    ) {
        parent::__construct($context, $projectGetter, $data);
        $this->serializer     = $serializer;
        $this->mageplazaPosts = $mageplazaPosts;
    }

    /**
     * @throws LocalizedException
     */
    public function getBlockGrid(): BlockInterface
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                MageplazaBlogs::class,
                'project.mageplazablog.grid'
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

        return $this->serializer->serialize($this->mageplazaPosts->getMageplazaPosts($project));
    }

    public function getInputName(): string
    {
        return 'mageplaza_blogs';
    }

    public function getGridParam(): string
    {
        return self::INCLUDED_MAGEPLAZA_BLOGS;
    }
}
