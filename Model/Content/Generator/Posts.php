<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Model\Content\Generator;

use EasyTranslate\CompatMageplazaBlog\Model\Config as CompatConfig;
use EasyTranslate\CompatMageplazaBlog\Model\Posts as PostsModel;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Content\Generator\AbstractGenerator;
use EasyTranslate\Connector\Model\Project as ProjectModel;
use EasyTranslate\Connector\Model\Staging\VersionManagerFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Mageplaza\Blog\Model\ResourceModel\Post\CollectionFactory;

class Posts extends AbstractGenerator
{
    public const ENTITY_CODE = 'posts';

    /**
     * @var string
     */
    protected $idField = 'post_id';

    /**
     * @var CollectionFactory
     */
    private $postCollectionFactory;

    /**
     * @var PostsModel
     */
    private $posts;

    public function __construct(
        Config $config,
        VersionManagerFactory $versionManagerFactory,
        CollectionFactory $postCollectionFactory,
        CompatConfig $compatConfig,
        PostsModel $posts
    ) {
        parent::__construct($config, $versionManagerFactory);
        $this->postCollectionFactory = $postCollectionFactory;
        $this->attributeCodes        = $compatConfig->getPostAttributes();
        $this->posts                 = $posts;
    }

    protected function getCollection(ProjectModel $project): AbstractDb
    {
        return $this->postCollectionFactory->create()
            ->addFieldToSelect($this->attributeCodes)
            ->addFieldToSelect($this->idField)
            ->addFieldToFilter('store_ids', ['in' => (int)$project->getData('source_store_id')])
            ->addAttributeToFilter($this->idField, ['in' => $this->posts->getPosts($project)]);
    }
}
