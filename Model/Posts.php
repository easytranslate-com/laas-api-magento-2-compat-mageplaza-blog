<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Model;

use EasyTranslate\Connector\Model\Project as ProjectModel;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

class Posts extends AbstractModel
{
    protected $_eventPrefix = 'easytranslate_project_mageplaza_blog_posts';

    public const POSTS = 'posts';

    public function __construct(
        Context $context,
        Registry $registry
    ) {
        parent::__construct($context, $registry);
        $this->_init(ResourceModel\Posts::class);
    }

    public function getPosts(ProjectModel $project): array
    {
        $posts = $this->getData(self::POSTS);
        if ($posts === null) {
            $posts = $this->getResource()->getPosts($project);
            $this->setData(self::POSTS, $posts);
        }

        return (array)$posts;
    }
}
