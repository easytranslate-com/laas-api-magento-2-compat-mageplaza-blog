<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Model\Content\Generator;

use EasyTranslate\CompatMageplazaBlog\Model\Config as CompatConfig;
use EasyTranslate\CompatMageplazaBlog\Model\Content\Generator\Filter\Posts as PostsFilter;
use EasyTranslate\CompatMageplazaBlog\Model\Posts as PostsModel;
use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\Config;
use EasyTranslate\Connector\Model\Content\Generator\AbstractGenerator;
use EasyTranslate\Connector\Model\Project as ProjectModel;
use EasyTranslate\Connector\Model\Staging\VersionManagerFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mageplaza\Blog\Api\Data\PostInterface;
use Mageplaza\Blog\Helper\Data as MageplazaHelper;
use Mageplaza\Blog\Model\ResourceModel\Post\CollectionFactory;

class Posts extends AbstractGenerator
{
    public const ENTITY_CODE = 'mageplaza_blog_posts';

    /**
     * @var string
     */
    protected $idField = PostInterface::URL_KEY;

    /**
     * @var CollectionFactory
     */
    private $postCollectionFactory;

    /**
     * @var PostsModel
     */
    private $posts;

    /**
     * @var MageplazaHelper
     */
    private $mageplazaHelper;

    /**
     * @var PostsFilter
     */
    private $postsFilter;

    public function __construct(
        Config $config,
        VersionManagerFactory $versionManagerFactory,
        CollectionFactory $postCollectionFactory,
        CompatConfig $compatConfig,
        PostsModel $posts,
        MageplazaHelper $mageplazaHelper,
        PostsFilter $postsFilter
    ) {
        parent::__construct($config, $versionManagerFactory);
        $this->postCollectionFactory = $postCollectionFactory;
        $this->attributeCodes        = $compatConfig->getPostAttributes();
        $this->posts                 = $posts;
        $this->mageplazaHelper       = $mageplazaHelper;
        $this->postsFilter           = $postsFilter;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function getCollection(ProjectModel $project): AbstractDb
    {
        $urlKeys = $this->postCollectionFactory->create()
            ->addFieldToFilter('post_id', ['in' => $this->posts->getPosts($project)])
            ->getColumnValues($this->idField);

        $postsCollection = $this->postCollectionFactory->create()
            ->addFieldToSelect($this->attributeCodes)
            ->addFieldToSelect($this->idField)
            ->addAttributeToFilter($this->idField, ['in' => $urlKeys]);
        $this->mageplazaHelper->addStoreFilter(
            $postsCollection,
            (int)$project->getData(ProjectInterface::SOURCE_STORE_ID)
        );

        return $this->postsFilter->filterEntities($postsCollection, $urlKeys);
    }
}
