<?php

declare(strict_types=1);

namespace EasyTranslate\CompatMageplazaBlog\Block\Adminhtml\Project\Tab;

use EasyTranslate\CompatMageplazaBlog\Model\ResourceModel\Posts as PostsResource;
use EasyTranslate\Connector\Block\Adminhtml\Project\Tab\AbstractEntity;
use EasyTranslate\Connector\Model\Adminhtml\ProjectGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Helper\Data;
use Magento\Framework\Data\Collection as CollectionData;
use Magento\Framework\Exception\LocalizedException;
use Mageplaza\Blog\Api\Data\PostInterface;
use Mageplaza\Blog\Helper\Data as MageplazaHelper;
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

    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        ProjectGetter $projectGetter,
        MageplazaHelper $mageplazaHelper,
        PostsResource $postsResource,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->setId('posts');
        $this->setDefaultSort(PostInterface::POST_ID);
        $this->setUseAjax(true);
        $this->collectionFactory = $collectionFactory;
        $this->projectGetter     = $projectGetter;
        $this->mageplazaHelper   = $mageplazaHelper;
        $this->postsResource     = $postsResource;
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
        if ($column->getId() === 'posts') {
            $postIds = $this->getSelectedPostIds();
            if (empty($postIds)) {
                $postIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('main_table.post_id', ['in' => $postIds]);
            } elseif (!empty($postIds)) {
                $this->getCollection()->addFieldToFilter('main_table.post_id', ['nin' => $postIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    protected function _prepareCollection(): Grid
    {
        /** @var Collection $postsCollection */
        $postsCollection = $this->collectionFactory->create();
        $postsCollection->addAttributeToSelect(PostInterface::NAME)
            ->addAttributeToSelect(PostInterface::POST_ID);
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            // join stores in which posts have already been added to a project / translated
            $projectPostsTable       = $postsCollection->getTable('easytranslate_project_posts');
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
        } else {
            $selectedPostIds = $this->getSelectedPostIds();
            $postsCollection->addFieldToFilter('main_table.post_id', ['in' => $selectedPostIds]);
        }
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        if ($storeId > 0) {
            $this->mageplazaHelper->addStoreFilter($postsCollection, $storeId);
        }
        $this->setCollection($postsCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if (!$this->projectGetter->getProject() || $this->projectGetter->getProject()->canEditDetails()) {
            $this->addColumn('posts', [
                'header_css_class' => 'a-center',
                'inline_css'       => 'in-project',
                'type'             => 'checkbox',
                'name'             => 'posts',
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
            'index'  => 'name'
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
        $includedPosts = $this->getRequest()->getPost('included_posts');
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
        return $this->getUrl('*/project_posts/grid', ['_current' => true]);
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
