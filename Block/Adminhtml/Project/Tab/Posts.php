<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project\Tab;

use EasyTranslate\CompatMageplazaBlog\Model\Posts as PostsModel;
use EasyTranslate\CompatMageplazaBlog\Model\ResourceModel\Posts as PostsResource;
use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\AbstractEntity;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Helper\Data;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Data\Collection as CollectionData;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Model\PageLayout\Config\BuilderInterface;
use Mageplaza\Blog\Api\Data\PostInterface;
use Mageplaza\Blog\Helper\Data as MageplazaHelper;
use Mageplaza\Blog\Model\Config\Source\AuthorStatus;
use Mageplaza\Blog\Model\ResourceModel\Post\Collection;
use Mageplaza\Blog\Model\ResourceModel\Post\CollectionFactory;

class Posts extends AbstractEntity
{
    /**
     * @var ProjectGetter
     */
    private $projectGetter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var MageplazaHelper
     */
    private $mageplazaHelper;

    /**
     * @var PostsResource
     */
    private $postsResource;

    /**
     * @var AuthorStatus
     */
    private $status;

    /**
     * @var Yesno
     */
    private $yesno;

    /**
     * @var BuilderInterface
     */
    private $pageLayoutBuilder;

    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        ProjectGetter $projectGetter,
        MageplazaHelper $mageplazaHelper,
        PostsResource $postsResource,
        AuthorStatus $status,
        Yesno $yesno,
        BuilderInterface $pageLayoutBuilder,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->setId('easytranslate_mageplaza_blog_posts');
        $this->setDefaultSort(PostInterface::POST_ID);
        $this->setUseAjax(true);
        $this->collectionFactory = $collectionFactory;
        $this->projectGetter     = $projectGetter;
        $this->mageplazaHelper   = $mageplazaHelper;
        $this->postsResource     = $postsResource;
        $this->status            = $status;
        $this->yesno             = $yesno;
        $this->pageLayoutBuilder = $pageLayoutBuilder;
    }

    /**
     * @param Column $column
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in posts flag
        if ($column->getId() === PostsModel::POSTS) {
            $postIds = $this->getSelectedPostIds();
            if (empty($postIds)) {
                $postIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('post_id', ['in' => $postIds]);
            } elseif (!empty($postIds)) {
                $this->getCollection()->addFieldToFilter('post_id', ['nin' => $postIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    protected function _prepareCollection(): Grid
    {
        $this->setDefaultFilter([PostsModel::POSTS => 1]);
        /** @var Collection $postsCollection */
        $postsCollection = $this->collectionFactory->create();
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            // join stores in which posts have already been added to a project / translated
            $projectPostsTable       = $postsCollection->getTable('easytranslate_project_mageplaza_blog_posts');
            $projectTargetStoreTable = $postsCollection->getTable('easytranslate_project_target_store');
            $postsCollection->getSelect()->joinLeft(
                ['etpmpb' => $projectPostsTable],
                'etpmpb.post_id=main_table.post_id',
                ['project_ids' => 'GROUP_CONCAT(DISTINCT etpmpb.project_id)']
            );
            $postsCollection->getSelect()->joinLeft(
                ['etpts' => $projectTargetStoreTable],
                'etpts.project_id=etpmpb.project_id',
                ['translated_stores' => 'GROUP_CONCAT(DISTINCT target_store_id)']
            );
            $postsCollection->getSelect()->group('main_table.post_id');
        } else {
            $selectedPostIds = $this->getSelectedPostIds();
            $postsCollection->addFieldToFilter('post_id', ['in' => $selectedPostIds]);
        }
        $sourceStoreId = $this->projectGetter->getProject()->getSourceStoreId();
        $this->mageplazaHelper->addStoreFilter($postsCollection, $sourceStoreId);
        $postsCollection->addFilterToMap('post_id', 'main_table.post_id');
        $this->setCollection($postsCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn(PostsModel::POSTS, [
                'header_css_class' => 'a-center',
                'inline_css'       => 'in-project',
                'type'             => 'checkbox',
                'name'             => PostsModel::POSTS,
                'values'           => $this->getSelectedPostIds(),
                'align'            => 'center',
                'index'            => PostInterface::POST_ID
            ]);
        }
        $this->addColumn(PostInterface::POST_ID, [
            'header'           => __('ID'),
            'sortable'         => true,
            'index'            => PostInterface::POST_ID,
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
        ]);
        $this->addColumn('title', [
            'header' => __('Name'),
            'index'  => PostInterface::NAME
        ]);
        $this->addColumn(PostInterface::URL_KEY, [
            'header' => __('Url Key'),
            'index'  => PostInterface::URL_KEY
        ]);
        $this->addColumn(PostInterface::ENABLED, [
            'header'           => __('Status'),
            'sortable'         => true,
            'index'            => PostInterface::ENABLED,
            'type'             => 'options',
            'options'          => $this->status->toArray(),
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
        ]);
        $this->addColumn(PostInterface::LAYOUT, [
            'header'           => __('Layout'),
            'index'            => PostInterface::LAYOUT,
            'type'             => 'options',
            'options'          => $this->pageLayoutBuilder->getPageLayoutsConfig()->getOptions(),
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
        ]);
        $this->addColumn(PostInterface::IN_RSS, [
            'header'  => __('In RSS'),
            'index'   => PostInterface::IN_RSS,
            'type'    => 'options',
            'options' => $this->yesno->toArray()
        ]);
        $this->addColumn(PostInterface::ALLOW_COMMENT, [
            'header'  => __('Allow Comments'),
            'index'   => PostInterface::ALLOW_COMMENT,
            'type'    => 'options',
            'options' => $this->yesno->toArray()
        ]);
        $this->addColumn(PostInterface::PUBLISH_DATE, [
            'header' => __('Published'),
            'index'  => PostInterface::PUBLISH_DATE,
            'type'   => 'datetime'
        ]);
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn(
                'translated_stores',
                [
                    'header'                    => __('Already Translated In'),
                    'width'                     => '250px',
                    'index'                     => 'translated_stores',
                    'type'                      => 'store',
                    'store_view'                => true,
                    'sortable'                  => false,
                    'store_all'                 => true,
                    'filter_condition_callback' => [$this, 'filterTranslatedCondition'],
                ]
            );
        }

        return parent::_prepareColumns();
    }

    private function getSelectedPostIds(): array
    {
        $project       = $this->projectGetter->getProject();
        $includedPosts = $this->getRequest()->getPost('included_mageplaza_blog_posts');
        if ($includedPosts === null) {
            if ($project) {
                return $this->postsResource->getPosts($project);
            }

            return [];
        }

        return explode(',', $includedPosts);
    }

    public function getGridUrl(): string
    {
        return $this->getUrl('easytranslate_mageplaza_blog/project_posts/grid', ['_current' => true]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    protected function filterTranslatedCondition(CollectionData $collection, Column $column): void
    {
        $value = $column->getFilter()->getValue();
        if ($value) {
            $collection->getSelect()->where('etpts.target_store_id=?', $value);
        }
    }
}
