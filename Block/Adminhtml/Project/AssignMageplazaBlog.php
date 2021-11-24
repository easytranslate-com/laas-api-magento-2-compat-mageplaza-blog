<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project;

use EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project\Tab\MageplazaBlogs;
use EasyTranslate\Connector\Block\Adminhtml\Project\AbstractBlock;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\BlockInterface;

class AssignMageplazaBlog extends AbstractBlock
{
    /**
     * @var MageplazaBlogs
     */
    private $blockGrid;

    /**
     * @var Json
     */
    private $jsonEncoder;

    public function __construct(
        Context $context,
        ProjectGetter $projectGetter,
        Json $jsonEncoder,
        array $data = []
    ) {
        parent::__construct($context, $projectGetter, $data);
        $this->jsonEncoder = $jsonEncoder;
    }

    private const INCLUDED_MAGEPLAZA_BLOGS = 'included_mageplaza_blogs[]';

    public function getInputName(): string
    {
        return 'mageplaza_blogs';
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
            return $this->jsonEncoder->serialize([]);
        }

        return '';
        // return $this->jsonEncoder->serialize($project->getBlogPosts());//TODO
    }

    public function getGridParam(): string
    {
        return self::INCLUDED_MAGEPLAZA_BLOGS;
    }
}
